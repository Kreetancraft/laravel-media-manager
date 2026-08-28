<?php

namespace Kreetancraft\Media\Listeners;

use Kreetancraft\Media\Jobs\ConvertMediaToWebp;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class DispatchWebpConversion
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $mime = $event->media->mime_type;

        if (! $mime || ! str_starts_with($mime, 'image/')) {
            return;
        }

        // Already WebP — no point re-encoding.
        if ($mime === 'image/webp') {
            return;
        }

        ConvertMediaToWebp::dispatch($event->media->id);
    }
}
