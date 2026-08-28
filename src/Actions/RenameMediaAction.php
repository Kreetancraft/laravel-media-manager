<?php

namespace Kreetancraft\Media\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RenameMediaAction
{
    use AsAction;

    public function __construct(
        private readonly MediaItemsContract $mediaItems,
    ) {}

    /**
     * Rename a media item, preserving its original file extension.
     */
    public function handle(Media $media, string $name): Media
    {
        $name = trim($name);
        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $fileName = Str::slug($name).'.'.$extension;

        $this->mediaItems->update($media, [
            'name' => $name,
            'file_name' => $fileName,
        ]);

        Log::info('Media renamed', [
            'media_id' => $media->id,
            'file_name' => $fileName,
            'updated_by' => auth()->id(),
        ]);

        return $media;
    }
}
