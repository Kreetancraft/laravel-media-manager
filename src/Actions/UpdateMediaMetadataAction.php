<?php

namespace Kreetancraft\Media\Actions;

use Kreetancraft\Media\Contracts\MediaItemsContract;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UpdateMediaMetadataAction
{
    use AsAction;

    public function __construct(
        private readonly MediaItemsContract $mediaItems,
    ) {}

    /**
     * Persist a single metadata field for a media item.
     *
     * The `title` field maps to the media's name column; `alt_text`, `caption`
     * and `description` are stored as custom properties.
     */
    public function handle(Media $media, string $key, string $value): void
    {
        $value = trim($value);

        if ($key === 'title') {
            $this->mediaItems->update($media, ['name' => $value]);

            return;
        }

        if (in_array($key, ['alt_text', 'caption', 'description'], true)) {
            $media->setCustomProperty($key, $value);
            $media->save();
        }
    }
}
