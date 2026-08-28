<?php

namespace Kreetancraft\Media\Concerns;

use Flux\Flux;
use Kreetancraft\Media\Actions\DeleteItemsAction;
use Kreetancraft\Media\Actions\MoveItemsAction;
use Kreetancraft\Media\Contracts\FolderContract;
use SanderMuller\FluentValidation\FluentRule as Rule;

trait HandlesBulkOperations
{
    /**
     * Selection Toggles.
     */
    public function toggleFolderSelection(int $folderId): void
    {
        if (in_array($folderId, $this->selectedFolders)) {
            $this->selectedFolders = array_diff($this->selectedFolders, [$folderId]);
        } else {
            $this->selectedFolders[] = $folderId;
        }
    }

    public function toggleFileSelection(int $fileId): void
    {
        if (in_array($fileId, $this->selectedFiles)) {
            $this->selectedFiles = array_diff($this->selectedFiles, [$fileId]);
        } else {
            $this->selectedFiles[] = $fileId;
        }
    }

    public function selectAll(): void
    {
        $this->selectedFolders = app(FolderContract::class)->folderIdsInParent($this->currentFolder->id);
        $this->selectedFiles = $this->currentFolder->media()->where('collection_name', 'medialibrary')->pluck('id')->toArray();
    }

    public function clearSelection(): void
    {
        $this->selectedFolders = [];
        $this->selectedFiles = [];
    }

    /**
     * Bulk Deletion.
     */
    public function deleteSelected(): void
    {
        $this->authorize('manage-media');

        if (count($this->selectedFolders) === 0 && count($this->selectedFiles) === 0) {
            return;
        }

        DeleteItemsAction::run($this->selectedFolders, $this->selectedFiles);

        $this->clearSelection();
        Flux::toast(variant: 'success', text: __('Successfully deleted selected items.'));
    }

    /**
     * Bulk move selected items to a target folder.
     */
    public function moveSelected(): void
    {
        $this->authorize('manage-media');

        $this->validate([
            'moveTargetFolderId' => Rule::numeric()->required()->exists('folders', 'id'),
        ]);

        MoveItemsAction::run($this->selectedFolders, $this->selectedFiles, (int) $this->moveTargetFolderId);

        $this->clearSelection();
        $this->moveTargetFolderId = null;
        Flux::toast(variant: 'success', text: __('Successfully moved items.'));
        $this->dispatch('close-modal', id: 'move-items-modal');
    }

    public function getMoveTargetFoldersProperty(): array
    {
        return app(FolderContract::class)
            ->moveTargetsExcluding($this->selectedFolders)
            ->all();
    }
}
