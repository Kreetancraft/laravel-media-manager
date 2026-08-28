<?php

namespace Kreetancraft\Media\Actions;

use Kreetancraft\Media\Models\MediaAttachment;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Returns the list of models a media item is attached to, via the
 * media_attachments pivot (many-to-many shared library links).
 *
 * @return array<int, array{type: string, id: int|string, title: string, collection: string, url: ?string}>
 */
class GetMediaUsageAction
{
    public function __invoke(Media $media): array
    {
        return $this->run($media);
    }

    /**
     * @return array<int, array{type: string, id: int|string, title: string, collection: string, url: ?string}>
     */
    public function run(Media $media): array
    {
        return MediaAttachment::query()
            ->where('media_id', $media->id)
            ->with('attachable')
            ->orderBy('created_at')
            ->get()
            ->map(function (MediaAttachment $attachment): array {
                $attachable = $attachment->attachable;

                $type = $attachable ? class_basename($attachable) : 'Removed';

                $title = '#'.$attachment->attachable_id;
                if ($attachable) {
                    $title = $attachable->title
                        ?? $attachable->name
                        ?? ($attachable->full_name ?? $title);
                }

                return [
                    'type' => $type,
                    'id' => $attachment->attachable_id,
                    'title' => (string) $title,
                    'collection' => $attachment->collection_name,
                    'url' => $this->urlFor($attachable),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Best-effort route resolution for known attachable types. Returns null
     * when no editor route is available so the UI can render plain text.
     */
    protected function urlFor(?object $attachable): ?string
    {
        if ($attachable === null) {
            return null;
        }

        return null;
    }
}
