<?php

namespace Kreetancraft\Media\Livewire;

use Kreetancraft\Media\Contracts\FolderContract;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Kreetancraft\Media\Livewire\Concerns\MediaPickerNavigation;
use Kreetancraft\Media\Livewire\Concerns\MediaPickerQuery;
use Kreetancraft\Media\Livewire\Concerns\MediaPickerSelection;
use Kreetancraft\Media\Livewire\Concerns\MediaPickerState;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Reusable library media picker — rebuilt to mirror the WordPress media modal.
 *
 *   <livewire:media.picker :multiple="true" group="trip-gallery" />
 *
 * Layout (WordPress-style):
 *   - Left rail: folder tree ("All media" + nested folders)
 *   - Center: breadcrumb + type/date filters + upload drop-zone + grid
 *   - Right rail: attachment details for the current selection (title/alt/caption,
 *     dimensions, URL) — editable, live-saved.
 */
class MediaPicker extends Component
{
    use MediaPickerNavigation;
    use MediaPickerQuery;
    use MediaPickerSelection;
    use MediaPickerState;
    use WithFileUploads;
    use WithPagination;

    private MediaItemsContract $mediaItems;

    private FolderContract $folders;

    public function boot(MediaItemsContract $mediaItems, FolderContract $folders): void
    {
        $this->mediaItems = $mediaItems;
        $this->folders = $folders;
    }

    public function mount(): void
    {
        $this->authorize('manage-media');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDate(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $current = $this->currentFolderId
            ? $this->folderMap()->firstWhere('id', $this->currentFolderId)
            : $this->homeFolder();

        $folders = $this->search !== ''
            ? collect()
            : $this->folderMap()
                ->where('parent_id', $current?->id)
                ->sortBy('name')
                ->values();

        $media = $this->paginatedMedia();

        $media->through(fn (Media $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'file_name' => $item->file_name,
            'mime' => $item->mime_type,
            'is_image' => str_starts_with((string) $item->mime_type, 'image/'),
            'size' => $item->size,
            'width' => $item->getCustomProperty('width'),
            'height' => $item->getCustomProperty('height'),
            'alt' => $item->getCustomProperty('alt_text', $item->name),
            'thumb' => $this->thumbUrl($item),
        ]);

        return view('media::livewire.picker', [
            'items' => $media,
            'folders' => $folders,
            'allFolders' => $this->folderTree(),
            'current' => $current,
            'ancestors' => $this->ancestorsOf($current),
            'uploadedDates' => $this->uploadedDates(),
            'selectedMedia' => $this->selectedMediaForDetails(),
        ]);
    }
}
