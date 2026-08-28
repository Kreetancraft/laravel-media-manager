<?php

namespace Kreetancraft\Media\Livewire;

use Flux\Flux;
use Kreetancraft\Media\Concerns\HandlesBulkOperations;
use Kreetancraft\Media\Concerns\HandlesFolderNavigation;
use Kreetancraft\Media\Concerns\HasMediaPagination;
use Kreetancraft\Media\Contracts\FolderContract;
use Kreetancraft\Media\Layout;
use Kreetancraft\Media\Models\MediaAttachment;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use LivewireFilemanager\Filemanager\Models\Folder;

class MediaGallery extends Component
{
    use HandlesBulkOperations;
    use HandlesFolderNavigation;
    use HasMediaPagination;
    use WithFileUploads;
    use WithPagination;

    /**
     * Active folder model.
     */
    public ?Folder $currentFolder = null;

    /**
     * Search term.
     */
    public string $search = '';

    /**
     * List of uploaded files (Livewire temporary uploads).
     */
    public $uploads = [];

    /**
     * Folder path breadcrumbs.
     */
    public array $breadcrumb = [];

    /**
     * For folder creation modal.
     */
    public string $newFolderName = '';

    /**
     * For renaming files/folders.
     */
    public string $editingName = '';

    /**
     * ID of the folder being renamed/deleted/moved.
     */
    public ?int $selectedFolderId = null;

    /**
     * ID of the media item being renamed/deleted/viewed.
     */
    public ?int $selectedMediaId = null;

    /**
     * ID of the media item shown in the details slide-over.
     */
    public ?int $detailsMediaId = null;

    /**
     * Multi-selection arrays.
     */
    public array $selectedFolders = [];

    public array $selectedFiles = [];

    /**
     * Target folder ID for moving items.
     */
    public ?int $moveTargetFolderId = null;

    /**
     * View mode: grid or list.
     */
    public string $viewMode = 'grid';

    /**
     * Filter by file type.
     */
    public string $filterType = 'all';

    /**
     * Filter by month/year.
     */
    public string $filterDate = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', MediaAttachment::class);

        $folders = app(FolderContract::class);

        // Resolve or create root Home folder
        $rootFolder = Folder::whereNull('parent_id')->first();
        if ($rootFolder === null) {
            $rootFolder = $folders->create([
                'name' => 'Home',
                'slug' => 'home',
                'parent_id' => null,
            ]);
        }

        $folderId = session('currentFolderId', $rootFolder->id);
        $this->currentFolder = $folders->find($folderId) ?? $rootFolder;

        session(['currentFolderId' => $this->currentFolder->id]);

        $this->updateNavigation();
    }

    /**
     * Open the details slide-over for a media item. The MediaDetails child
     * component loads the media, metadata, and usage on mount.
     */
    public function openDetails(int $mediaId): void
    {
        $this->detailsMediaId = $mediaId;
        Flux::modal('media-details')->show();
    }

    /**
     * The details slide-over deleted a media item — clear the reference and
     * let the grid refresh.
     */
    #[On('media-deleted')]
    public function onMediaDeleted(): void
    {
        $this->detailsMediaId = null;
    }

    #[Title('Media Gallery - Admin')]
    public function render()
    {
        return view('media::livewire.index', [
            'subfolders' => $this->getSubfolders(),
            'files' => $this->getFiles(),
            'uploadedDates' => $this->getUploadedDates(),
            'navigationIds' => $this->getNavigationIds(),
        ])->layout(Layout::admin());
    }
}
