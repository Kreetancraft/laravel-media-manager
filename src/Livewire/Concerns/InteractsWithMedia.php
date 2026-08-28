<?php

namespace Kreetancraft\Media\Livewire\Concerns;

/**
 * Provides media-normalisation helpers for Livewire components that work with
 * the shared media library picker (media-picked events).
 */
trait InteractsWithMedia
{
    /**
     * Normalise raw media-picked event items into the standard
     * {id, url, name} shape.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{id: int, url: string, name: string}>
     */
    protected function normalizeMediaItems(array $items): array
    {
        return array_values(array_map(fn (array $i) => [
            'id' => (int) ($i['id'] ?? 0),
            'url' => (string) ($i['url'] ?? ''),
            'name' => (string) ($i['name'] ?? ''),
        ], $items));
    }

    /**
     * Extract a flat list of media IDs from the standard {id, url, name} array.
     *
     * @param  array<int, array{id: int, url?: string, name?: string}>  $items
     * @return array<int, int>
     */
    protected function idsOf(array $items): array
    {
        return array_values(array_map(fn ($i) => (int) ($i['id'] ?? 0), $items));
    }
}
