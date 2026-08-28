<?php

use Illuminate\Database\Eloquent\Model;
use Kreetancraft\Media\Support\MediaUrl;

if (! function_exists('mediaItemsFor')) {
    /**
     * The model's attached media in the shape the media picker/tiles expect.
     *
     * @return array<int, array{id: int, url: string, name: string}>
     */
    function mediaItemsFor(Model $model, string $collection): array
    {
        return $model->attachedMedia($collection)->map(fn ($media) => [
            'id' => $media->id,
            'url' => MediaUrl::publicFor($media),
            'name' => $media->name,
        ])->all();
    }
}

if (! function_exists('normalizeMediaItems')) {
    /**
     * Normalise raw media-picked event items.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{id: int, url: string, name: string}>
     */
    function normalizeMediaItems(array $items): array
    {
        return array_values(array_map(fn (array $item) => [
            'id' => (int) ($item['id'] ?? 0),
            'url' => (string) ($item['url'] ?? ''),
            'name' => (string) ($item['name'] ?? ''),
        ], $items));
    }
}
