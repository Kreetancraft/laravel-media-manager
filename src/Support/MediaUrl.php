<?php

namespace Kreetancraft\Media\Support;

use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Public URL for a media item.
 *
 * Media attached to a Folder is served through this package's asset route so the
 * folder hierarchy shows up in the URL; everything else uses the disk URL that
 * medialibrary already knows how to build.
 */
class MediaUrl
{
    public static function publicFor(Media $media, ?string $conversion = null): string
    {
        $conversion = $conversion !== null && $media->hasGeneratedConversion($conversion)
            ? $conversion
            : null;

        if ($media->model_type !== Folder::class) {
            return self::diskUrl($media, $conversion);
        }

        $folderPath = self::folderPath((int) $media->model_id);

        if ($folderPath === '') {
            return self::diskUrl($media, $conversion);
        }

        $route = (string) config('media.routes.names.asset', 'media.asset');

        // The host may point this at its own controller, or disable the route
        // entirely. A missing route should not take a page down over one image.
        if (! app('router')->has($route)) {
            return self::diskUrl($media, $conversion);
        }

        $params = ['path' => $folderPath.'/'.$media->file_name];

        if ($conversion) {
            $params['conversion'] = $conversion;
        }

        return route($route, $params);
    }

    private static function diskUrl(Media $media, ?string $conversion): string
    {
        return $conversion ? $media->getFullUrl($conversion) : $media->getFullUrl();
    }

    /**
     * Slug path of a folder, memoised for the request.
     */
    private static function folderPath(int $folderId): string
    {
        static $cache = [];

        if (array_key_exists($folderId, $cache)) {
            return $cache[$folderId];
        }

        $cache[$folderId] = function_exists('buildFolderPath')
            ? (string) buildFolderPath($folderId)
            : self::walkFolderPath($folderId);

        return $cache[$folderId];
    }

    private static function walkFolderPath(int $folderId): string
    {
        $folder = Folder::find($folderId);

        if (! $folder) {
            return '';
        }

        $segments = [];
        $cursor = $folder;

        while ($cursor && $cursor->parent_id !== null) {
            array_unshift($segments, $cursor->slug);
            $cursor = $cursor->parent;
        }

        return implode('/', $segments);
    }
}
