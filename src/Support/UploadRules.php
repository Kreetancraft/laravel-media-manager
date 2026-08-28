<?php

namespace Kreetancraft\Media\Support;

use Illuminate\Validation\Rules\File;

/**
 * One definition of what may be uploaded.
 *
 * The picker and the gallery previously carried separate rules and disagreed:
 * the gallery restricted extensions, the picker accepted anything. Both now read
 * from here, so they cannot drift apart again.
 */
final class UploadRules
{
    /**
     * Validation rule for a single uploaded file.
     */
    public static function file(): File
    {
        return File::default()
            ->max(self::maxSizeKb())
            ->rules(['mimes:'.implode(',', self::allowedExtensions())]);
    }

    /**
     * Rule array keyed for `uploads.*`.
     *
     * @return array<string, mixed>
     */
    public static function forUploads(string $key = 'uploads.*'): array
    {
        return [$key => ['required', self::file()]];
    }

    public static function maxSizeKb(): int
    {
        return (int) config('media.uploads.max_size_kb', 10240);
    }

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return array_values((array) config('media.uploads.allowed_extensions', [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf',
        ]));
    }

    /**
     * @return list<string>
     */
    public static function allowedMimes(): array
    {
        return array_values((array) config('media.uploads.allowed_mimes', [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
        ]));
    }
}
