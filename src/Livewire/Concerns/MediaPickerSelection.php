<?php

namespace Kreetancraft\Media\Livewire\Concerns;

use Flux\Flux;
use Kreetancraft\Media\Support\MediaUrl;
use Livewire\Attributes\On;

trait MediaPickerSelection
{
    #[On('media-picker-set-selection')]
    public function setSelection(array $ids, string $group = 'default'): void
    {
        if ($group !== $this->group) {
            return;
        }

        $this->selected = array_values(array_unique(array_map('intval', $ids)));
    }

    public function toggle(int $mediaId): void
    {
        if (in_array($mediaId, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$mediaId]));

            return;
        }

        $this->selected = $this->multiple ? [...$this->selected, $mediaId] : [$mediaId];
    }

    public function isSelected(int $mediaId): bool
    {
        return in_array($mediaId, $this->selected, true);
    }

    public function selectAllOnPage(): void
    {
        $ids = $this->paginatedMedia()->pluck('id')->all();
        $this->selected = $this->multiple
            ? array_values(array_unique([...$this->selected, ...$ids]))
            : ($ids[0] ?? []);
    }

    public function clear(): void
    {
        $this->selected = [];
    }

    public function confirm(): void
    {
        $this->authorize('manage-media');

        $items = $this->mediaItems
            ->findWhereIn($this->selected)
            ->map(fn ($media): array => [
                'id' => $media->id,
                'name' => $media->name,
                'url' => MediaUrl::publicFor($media),
            ])
            ->values()
            ->all();

        $this->dispatch('media-picked', ids: array_values($this->selected), group: $this->group, items: $items);

        Flux::modal('media-picker-'.$this->group)->close();
    }

    public function saveDetail(int $mediaId, string $field, $value): void
    {
        $this->authorize('manage-media');

        $media = $this->mediaItems->find($mediaId);

        if ($media === null) {
            return;
        }

        if ($field === 'title') {
            $media->name = $value;
        } else {
            $media->setCustomProperty($field, $value);
        }

        $media->save();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function selectedMediaForDetails(): ?array
    {
        $id = end($this->selected);

        if ($id === false || $id === null) {
            return null;
        }

        $media = $this->mediaItems->find($id);

        if (! $media) {
            return null;
        }

        return [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime' => $media->mime_type,
            'size' => $media->size,
            'is_image' => str_starts_with((string) $media->mime_type, 'image/'),
            'width' => $media->getCustomProperty('width'),
            'height' => $media->getCustomProperty('height'),
            'alt' => $media->getCustomProperty('alt_text', ''),
            'caption' => $media->getCustomProperty('caption', ''),
            'description' => $media->getCustomProperty('description', ''),
            'thumb' => $this->thumbUrl($media),
            'url' => MediaUrl::publicFor($media),
        ];
    }
}
