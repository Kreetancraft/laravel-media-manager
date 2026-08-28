<?php

namespace Kreetancraft\Media\Concerns;

use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Kreetancraft\Media\Actions\CreateFolderAction;
use Kreetancraft\Media\Actions\DeleteFolderAction;
use Kreetancraft\Media\Actions\DeleteMediaAction;
use Kreetancraft\Media\Actions\MoveItemsAction;
use Kreetancraft\Media\Actions\RenameFolderAction;
use Kreetancraft\Media\Actions\RenameMediaAction;
use Kreetancraft\Media\Actions\UploadMediaAction;
use Kreetancraft\Media\Contracts\FolderContract;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Kreetancraft\Media\Models\MediaAttachment;
use Kreetancraft\Media\Support\UploadRules;
use SanderMuller\FluentValidation\FluentRule as Rule;
use Throwable;

trait HandlesFolderNavigation
{
    /**
     * Escape LIKE wildcards (% and _) to prevent SQL injection via search.
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * Livewire hook that fires when uploads is updated.
     */
    public function updatedUploads(): void
    {
        $this->authorize('create', MediaAttachment::class);

        try {
            $this->validate([
                'uploads.*' => ['required', UploadRules::file()],
            ]);

            foreach ($this->uploads as $file) {
                UploadMediaAction::run($this->currentFolder, $file, auth()->id());
            }

            $this->uploads = [];
            $this->clearSelection();
            Flux::toast(variant: 'success', text: __('Files uploaded successfully.'));
        } catch (ValidationException $e) {
            $this->uploads = [];
            Flux::toast(variant: 'error', text: __('File upload failed: ').$e->validator->errors()->first());
        } catch (Throwable $e) {
            // Anything non-validation (unwritable disk, image decode failure,
            // misconfigured filesystem) used to escape uncaught, leaving the
            // upload spinner running with nothing reported to the user.
            $this->uploads = [];

            Log::error('Media upload failed', [
                'folder_id' => $this->currentFolder->id ?? null,
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);

            Flux::toast(variant: 'error', text: __('File upload failed. Please try again or contact support.'));
        }
    }

    /**
     * Create a new subfolder in the current directory.
     */
    public function createFolder(): void
    {
        $this->authorize('create', MediaAttachment::class);

        $this->validate([
            'newFolderName' => Rule::string()->required()->max(255)->rule(
                function ($attribute, $value, $fail) {
                    $slug = Str::slug(trim($value));
                    $exists = app(FolderContract::class)->slugExistsInParent($slug, $this->currentFolder->id);

                    if ($exists) {
                        $fail(__('Folder already exists in this directory.'));
                    }

                    $maxDepth = config('livewire-filemanager.folders.max_depth');
                    if ($maxDepth !== null && $this->currentFolder->getDepth() >= $maxDepth - 1) {
                        $fail(__('Maximum folder depth of :max exceeded.', ['max' => $maxDepth]));
                    }
                },
            ),
        ]);

        CreateFolderAction::run($this->currentFolder, $this->newFolderName);

        $this->newFolderName = '';
        $this->clearSelection();
        Flux::toast(variant: 'success', text: __('Folder created successfully.'));
        $this->dispatch('close-modal', id: 'create-folder-modal');
    }

    /**
     * Navigate into a folder.
     */
    public function navigateToFolder(int $folderId): void
    {
        $folder = app(FolderContract::class)->find($folderId);
        if ($folder !== null) {
            $this->currentFolder = $folder;
            $this->search = '';
            session(['currentFolderId' => $folder->id]);
            $this->clearSelection();
            $this->updateNavigation();
            $this->resetPage();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Navigate up to parent folder.
     */
    public function navigateToParent(): void
    {
        if ($this->currentFolder->parent_id !== null) {
            $this->navigateToFolder($this->currentFolder->parent_id);
        }
    }

    /**
     * Delete a folder.
     */
    public function deleteFolder(int $folderId): void
    {
        $this->authorize('delete', MediaAttachment::class);

        $folder = app(FolderContract::class)->find($folderId);
        if ($folder === null) {
            return;
        }

        if ($folder->isHomeFolder()) {
            Flux::toast(variant: 'error', text: __('Root folder cannot be deleted.'));

            return;
        }

        DeleteFolderAction::run($folder);
        $this->clearSelection();
        Flux::toast(variant: 'success', text: __('Folder deleted successfully.'));
    }

    /**
     * Open renaming folder modal.
     */
    public function startFolderRename(int $folderId): void
    {
        $folder = app(FolderContract::class)->find($folderId);
        if ($folder !== null) {
            $this->selectedFolderId = $folderId;
            $this->editingName = $folder->name;
            $this->dispatch('open-modal', id: 'rename-folder-modal');
        }
    }

    /**
     * Save folder name change.
     */
    public function renameFolder(): void
    {
        $this->authorize('update', MediaAttachment::class);

        $folder = app(FolderContract::class)->find($this->selectedFolderId);
        if ($folder === null) {
            return;
        }

        $this->validate([
            'editingName' => Rule::string()->required()->max(255)->rule(
                function ($attribute, $value, $fail) use ($folder) {
                    $slug = Str::slug(trim($value));
                    $exists = app(FolderContract::class)->slugExistsInParent($slug, $folder->parent_id, $folder->id);

                    if ($exists) {
                        $fail(__('Folder already exists in this directory.'));
                    }
                },
            ),
        ]);

        RenameFolderAction::run($folder, $this->editingName);

        $this->editingName = '';
        $this->selectedFolderId = null;
        Flux::toast(variant: 'success', text: __('Folder renamed successfully.'));
        $this->dispatch('close-modal', id: 'rename-folder-modal');
    }

    /**
     * Delete a media item.
     */
    public function deleteFile(int $mediaId): void
    {
        $this->authorize('delete', MediaAttachment::class);

        $media = app(MediaItemsContract::class)->find($mediaId);
        if ($media !== null) {
            DeleteMediaAction::run($media);
            $this->selectedMediaId = null;
            $this->clearSelection();
            Flux::toast(variant: 'success', text: __('File deleted successfully.'));
            $this->dispatch('close-modal', id: 'file-details-modal');
        }
    }

    /**
     * Open renaming file modal.
     */
    public function startFileRename(int $mediaId): void
    {
        $media = app(MediaItemsContract::class)->find($mediaId);
        if ($media !== null) {
            $this->selectedMediaId = $mediaId;
            $this->editingName = pathinfo($media->file_name, PATHINFO_FILENAME);
            $this->dispatch('open-modal', id: 'rename-file-modal');
        }
    }

    /**
     * Save file name change.
     */
    public function renameFile(): void
    {
        $this->authorize('update', MediaAttachment::class);

        $media = app(MediaItemsContract::class)->find($this->selectedMediaId);
        if ($media === null) {
            return;
        }

        $this->validate([
            'editingName' => Rule::string()->required()->max(255),
        ]);

        RenameMediaAction::run($media, $this->editingName);

        $this->editingName = '';
        $this->selectedMediaId = null;
        Flux::toast(variant: 'success', text: __('File renamed successfully.'));
        $this->dispatch('close-modal', id: 'rename-file-modal');
    }

    /**
     * Move items logic.
     */
    public function moveItem(int $itemId, string $type, int $targetFolderId): void
    {
        $this->authorize('update', MediaAttachment::class);

        if ($type === 'folder') {
            MoveItemsAction::run([$itemId], [], $targetFolderId);
            Flux::toast(variant: 'success', text: __('Moved folder successfully.'));
        } else {
            MoveItemsAction::run([], [$itemId], $targetFolderId);
            Flux::toast(variant: 'success', text: __('Moved file successfully.'));
        }

        $this->clearSelection();
    }

    /**
     * Update path breadcrumbs.
     */
    private function updateNavigation(): void
    {
        $this->breadcrumb = app(FolderContract::class)->breadcrumbTo($this->currentFolder);
    }
}
