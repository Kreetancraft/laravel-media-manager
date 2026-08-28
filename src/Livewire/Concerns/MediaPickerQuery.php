<?php

namespace Kreetancraft\Media\Livewire\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Kreetancraft\Media\Support\MediaUrl;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait MediaPickerQuery
{
    /**
     * @return LengthAwarePaginator<int, Media>
     */
    private function paginatedMedia()
    {
        $mimePattern = $this->resolveMimePattern();

        return Media::query()
            ->where('collection_name', 'medialibrary')
            ->where('mime_type', 'like', $mimePattern)
            ->when($this->search !== '', function ($query) {
                $safeSearch = $this->escapeLike($this->search);
                $query->where(function ($q) use ($safeSearch) {
                    $q->where('name', 'like', "%{$safeSearch}%")
                        ->orWhere('file_name', 'like', "%{$safeSearch}%");
                });
            })
            ->when($this->search === '' && ! $this->includeSubfolders, function ($query) {
                $query->when($this->currentFolderId, fn ($q) => $q
                    ->where('model_type', Folder::class)
                    ->where('model_id', $this->currentFolderId))
                    ->when($this->currentFolderId === null, fn ($q) => $q
                        ->where('model_type', Folder::class)
                        ->where('model_id', $this->homeFolder()?->id));
            })
            ->when($this->search !== '' || $this->includeSubfolders, function ($query) {
                if ($this->includeSubfolders && $this->currentFolderId) {
                    $ids = $this->descendantFolderIds($this->currentFolderId);
                    $ids[] = $this->currentFolderId;
                    $query->whereIn('model_id', $ids)
                        ->where('model_type', Folder::class);
                }
            })
            ->when($this->filterDate !== '', function ($query) {
                $query->whereYear('created_at', substr($this->filterDate, 0, 4))
                    ->whereMonth('created_at', substr($this->filterDate, 5, 2));
            })
            ->latest('id')
            ->paginate(18);
    }

    private function resolveMimePattern(): string
    {
        if ($this->filterType === 'image') {
            return 'image/%';
        }

        if ($this->filterType === 'document') {
            return 'application/%';
        }

        return $this->mimeType;
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * @return Collection<int, string>
     */
    private function uploadedDates(): Collection
    {
        $months = $this->mediaItems->monthsWithUploads();

        return collect($months)->pluck('value');
    }

    private function thumbUrl(Media $item): string
    {
        if (! str_starts_with((string) $item->mime_type, 'image/')) {
            return '';
        }

        try {
            if ($item->hasGeneratedConversion('thumbnail')) {
                return MediaUrl::publicFor($item, 'thumbnail');
            }
        } catch (\Throwable) {
        }

        return $item->getCustomProperty('webp_url') ?: MediaUrl::publicFor($item);
    }
}
