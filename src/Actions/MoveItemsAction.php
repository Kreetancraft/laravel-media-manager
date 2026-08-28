<?php

namespace Kreetancraft\Media\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreetancraft\Media\Contracts\FolderContract;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Lorisleiva\Actions\Concerns\AsAction;

class MoveItemsAction
{
    use AsAction;

    public function __construct(
        private readonly FolderContract $folders,
        private readonly MediaItemsContract $mediaItems,
    ) {}

    /**
     * Move the given folders and media items into a target folder.
     *
     * Skips the protected root folder, no-op self moves, and moves that would
     * create a circular folder structure.
     *
     * @param  array<int, int>  $folderIds
     * @param  array<int, int>  $fileIds
     */
    public function handle(array $folderIds, array $fileIds, int $targetFolderId): void
    {
        DB::transaction(function () use ($folderIds, $fileIds, $targetFolderId) {
            foreach ($folderIds as $folderId) {
                if ($folderId === $targetFolderId) {
                    continue;
                }

                $folder = $this->folders->find($folderId);
                if ($folder !== null && ! $folder->isHomeFolder() && ! $this->isChildOf($folder->id, $targetFolderId)) {
                    $this->folders->update($folder, ['parent_id' => $targetFolderId]);
                }
            }

            foreach ($fileIds as $fileId) {
                $media = $this->mediaItems->find($fileId);
                if ($media !== null) {
                    $this->mediaItems->update($media, ['model_id' => $targetFolderId]);
                }
            }
        });

        Log::info('Media items moved', [
            'folder_ids' => $folderIds,
            'file_ids' => $fileIds,
            'target_folder_id' => $targetFolderId,
            'moved_by' => auth()->id(),
        ]);
    }

    /**
     * Determine whether $parentId lives inside the subtree rooted at $childId,
     * which would make moving $childId into $parentId circular.
     */
    private function isChildOf(int $childId, int $parentId): bool
    {
        if ($childId === $parentId) {
            return true;
        }

        $current = $this->folders->find($parentId);
        $depth = 0;

        while ($current !== null && $current->parent_id !== null && $depth < 50) {
            if ($current->parent_id === $childId) {
                return true;
            }

            $current = $this->folders->find($current->parent_id);
            $depth++;
        }

        return false;
    }
}
