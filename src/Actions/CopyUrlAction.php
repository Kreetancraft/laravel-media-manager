<?php

namespace Kreetancraft\Media\Actions;

use Kreetancraft\Media\Support\MediaUrl;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Resolves the public, shareable URL for a media item (optionally for a
 * specific conversion / responsive size).
 */
class CopyUrlAction
{
    public function __invoke(Media $media, ?string $conversion = null): string
    {
        return $this->run($media, $conversion);
    }

    public function run(Media $media, ?string $conversion = null): string
    {
        return MediaUrl::publicFor($media, $conversion);
    }

    /**
     * Markdown image snippet for pasting into rich-text editors.
     */
    public function markdown(Media $media, ?string $conversion = null): string
    {
        $url = $this->run($media, $conversion);
        $alt = $media->getCustomProperty('alt_text', $media->name);

        return "![{$alt}]({$url})";
    }
}
