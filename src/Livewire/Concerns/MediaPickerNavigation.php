<?php

namespace Kreetancraft\Media\Livewire\Concerns;

use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Kreetancraft\Media\Actions\UploadMediaAction;
use Kreetancraft\Media\Models\MediaAttachment;
use Kreetancraft\Media\Support\UploadRules;
use LivewireFilemanager\Filemanager\Models\Folder;
use Throwable;

trait MediaPickerNavigation
{
    public function openFolder(?int $folderId): void
    {
        $this->currentFolderId = $folderId;
        $this->search = '';
        $this->resetPage();
    }

    public function goUp(): void
    {
        if ($this->currentFolderId === null) {
            return;
        }

        $parent = $this->folders->find($this->currentFolderId)?->parent_id;
        $this->openFolder($parent);
    }

    public function toggleSubfolders(): void
    {
        $this->includeSubfolders = ! $this->includeSubfolders;
        $this->resetPage();
    }

    public function updatedUploads(): void
    {
        $this->authorize('create', MediaAttachment::class);

        try {
            $this->validate(UploadRules::forUploads());

            $folder = $this->currentFolderId
                ? $this->folders->findOrFail($this->currentFolderId)
                : $this->homeFolder();

            foreach ($this->uploads as $upload) {
                UploadMediaAction::run($folder, $upload, auth()->id());
            }

            $this->uploads = [];
            $this->resetPage();
            Flux::toast(variant: 'success', text: __('Uploaded to :folder.', ['folder' => $folder->name]));
        } catch (ValidationException $e) {
            $this->uploads = [];
            Flux::toast(variant: 'error', text: __('File upload failed: ').$e->validator->errors()->first());
        } catch (Throwable $e) {
            $this->uploads = [];

            Log::error('Media picker upload failed', [
                'folder_id' => $this->currentFolderId,
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);

            Flux::toast(variant: 'error', text: __('File upload failed. Please try again or contact support.'));
        }
    }

    /**
     * @return Collection<int, Folder>
     */
    private function folderMap(): Collection
    {
        if ($this->folderCache === null) {
            $this->folderCache = Folder::query()
                ->without('children')
                ->orderBy('name')
                ->get();
        }

        return $this->folderCache;
    }

    /**
     * @return Collection<int, array{folder: Folder, depth: int, children: Collection}>
     */
    private function folderTree(): Collection
    {
        $childrenOf = $this->folderMap()->groupBy('parent_id');

        $build = function (?int $parentId, int $depth) use (&$build, $childrenOf): Collection {
            $nodes = $childrenOf->get($parentId, collect());

            if ($nodes->isEmpty()) {
                return collect();
            }

            return $nodes->flatMap(function (Folder $folder) use (&$build, $depth, $childrenOf): array {
                $kids = $childrenOf->get($folder->id, collect());

                return [
                    ['folder' => $folder, 'depth' => $depth, 'children' => $kids],
                    ...($kids->isNotEmpty() ? $build($folder->id, $depth + 1)->all() : []),
                ];
            });
        };

        return $build(null, 0);
    }

    /**
     * @return array<int, int>
     */
    private function descendantFolderIds(int $rootId): array
    {
        $childrenOf = $this->folderMap()->groupBy('parent_id');

        $ids = [];
        $queue = [$rootId];

        while ($queue !== []) {
            $id = array_shift($queue);

            foreach ($childrenOf->get($id, collect()) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }

    /**
     * @return Collection<int, Folder>
     */
    private function ancestorsOf(?Folder $folder): Collection
    {
        if (! $folder) {
            return collect();
        }

        $map = $this->folderMap()->keyBy('id');

        $chain = collect([$folder]);
        $parentId = $folder->parent_id;

        while ($parentId !== null) {
            $parent = $map->get($parentId);

            if (! $parent) {
                break;
            }

            $chain->prepend($parent);
            $parentId = $parent->parent_id;
        }

        return $chain;
    }

    private function homeFolder(): ?Folder
    {
        return $this->folderMap()->firstWhere('parent_id', null);
    }
}
