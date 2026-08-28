<?php

namespace Kreetancraft\Media\Actions;

use Kreetancraft\Media\Contracts\MediaItemsContract;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GenerateAltTextAction
{
    use AsAction;

    public function __construct(
        private readonly MediaItemsContract $mediaItems,
    ) {}

    /**
     * Generate descriptive alt text from a media item's file name and persist it.
     *
     * Returns the generated alt text so the caller can reflect it in the UI.
     */
    public function handle(Media $media): string
    {
        $name = pathinfo($media->file_name, PATHINFO_FILENAME);
        $cleanName = ucwords(str_replace(['-', '_'], ' ', $name));

        $altText = "An image showing {$cleanName}";

        $lowerName = strtolower($cleanName);
        if (str_contains($lowerName, 'trek') || str_contains($lowerName, 'annapurna') || str_contains($lowerName, 'nepal')) {
            $altText .= ' during a trekking adventure in the Himalayas, Nepal';
        }

        $media->setCustomProperty('alt_text', $altText);
        $media->save();

        return $altText;
    }
}
