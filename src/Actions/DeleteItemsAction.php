<?php

namespace Kreetancraft\Media\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreetancraft\Media\Contracts\FolderContract;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteItemsAction
{
    use AsAction;

    public function __construct(
        private readonly FolderContract $folders,
        private readonly MediaItemsContract $mediaItems,
    ) {}

    /**
     * Bulk-delete the given folders and media items.
     *
     * The protected root folder is always skipped.
     *
     * @param  array<int, int>  $folderIds
     * @param  array<int, int>  $fileIds
     */
    public function handle(array $folderIds, array $fileIds): void
    {
        DB::transaction(function () use ($folderIds, $fileIds) {
            foreach ($folderIds as $folderId) {
                $folder = $this->folders->find($folderId);
                if ($folder !== null && ! $folder->isHomeFolder()) {
                    $this->folders->delete($folder);
                }
            }

            foreach ($fileIds as $fileId) {
                $media = $this->mediaItems->find($fileId);
                if ($media !== null) {
                    $this->mediaItems->delete($media);
                }
            }
        });

        Log::info('Media items bulk deleted', [
            'folder_ids' => $folderIds,
            'file_ids' => $fileIds,
            'deleted_by' => auth()->id(),
        ]);
    }
}
