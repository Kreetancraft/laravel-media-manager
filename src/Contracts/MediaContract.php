<?php

namespace Kreetancraft\Media\Contracts;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface MediaContract
{
    /**
     * Attach a file to a model's media collection.
     *
     * Returns the URL of the requested conversion (or the original if conversion is empty).
     */
    public function attach(HasMedia $owner, UploadedFile|string $file, string $collection, ?string $name = null, string $conversion = ''): string;

    /**
     * Remove every media item from the given collection.
     */
    public function clear(HasMedia $owner, string $collection): void;

    /**
     * Get the URL of the first media item in a collection, with optional conversion.
     *
     * Returns null if the collection is empty.
     */
    public function urlFor(HasMedia $owner, string $collection, string $conversion = ''): ?string;

    /**
     * Get the auto-generated WebP URL for a media item, if one was produced
     * by the DispatchWebpConversion listener + ConvertMediaToWebp job.
     *
     * Returns null when no WebP variant exists (e.g. SVG, PDF, or already-WebP source).
     */
    public function webpUrlFor(Media $media): ?string;

    /**
     * Get the public URL for a specific Media model item, with optional conversion.
     */
    public function publicUrl(Media $media, ?string $conversion = null): string;
}
