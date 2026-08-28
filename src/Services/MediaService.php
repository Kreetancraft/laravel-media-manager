<?php

namespace Kreetancraft\Media\Services;

use Illuminate\Http\UploadedFile;
use Kreetancraft\Media\Contracts\MediaContract;
use Kreetancraft\Media\Support\MediaUrl;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaService implements MediaContract
{
    public function attach(HasMedia $owner, UploadedFile|string $file, string $collection, ?string $name = null, string $conversion = ''): string
    {
        $adder = $owner->addMedia($file);

        if ($name !== null) {
            $adder = $adder->usingFileName($name);
        }

        $adder->toMediaCollection($collection);

        return $owner->getFirstMediaUrl($collection, $conversion);
    }

    public function clear(HasMedia $owner, string $collection): void
    {
        $owner->clearMediaCollection($collection);
    }

    public function urlFor(HasMedia $owner, string $collection, string $conversion = ''): ?string
    {
        $url = $owner->getFirstMediaUrl($collection, $conversion);

        return $url !== '' ? $url : null;
    }

    public function webpUrlFor(Media $media): ?string
    {
        $url = $media->getCustomProperty('webp_url');

        return is_string($url) && $url !== '' ? $url : null;
    }

    public function publicUrl(Media $media, ?string $conversion = null): string
    {
        return MediaUrl::publicFor($media, $conversion);
    }
}
