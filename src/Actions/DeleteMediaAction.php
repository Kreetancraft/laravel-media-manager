<?php

namespace Kreetancraft\Media\Actions;

use Illuminate\Support\Facades\Log;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DeleteMediaAction
{
    use AsAction;

    public function __construct(
        private readonly MediaItemsContract $mediaItems,
    ) {}

    /**
     * Delete a media item and its underlying files.
     */
    public function handle(Media $media): void
    {
        $this->mediaItems->delete($media);

        Log::info('Media deleted', [
            'media_id' => $media->id,
            'deleted_by' => auth()->id(),
        ]);
    }
}
