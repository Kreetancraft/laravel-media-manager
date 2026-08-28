<?php

namespace Kreetancraft\Media\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Kreetancraft\Media\Contracts\FolderContract;
use LivewireFilemanager\Filemanager\Models\Folder;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UploadMediaAction
{
    use AsAction;

    public function __construct(
        private readonly FolderContract $folders,
    ) {}

    /**
     * Mime types that are transcoded to WebP on upload.
     */
    private const CONVERTIBLE_MIMES = ['image/jpeg', 'image/jpg', 'image/png'];

    /**
     * Add a single uploaded file to a folder's media collection.
     *
     * JPEG/PNG uploads are transcoded to a size-capped WebP; everything else is
     * stored as-is with a slugified file name.
     */
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf', 'application/zip', 'image/avif',
    ];

    public function handle(Folder $folder, UploadedFile $file, ?int $userId = null): Media
    {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'file' => __('File type :mime is not allowed.', ['mime' => $file->getMimeType()]),
            ]);
        }

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
        $customProperties = ['user_id' => $userId];

        if (in_array($file->getMimeType(), self::CONVERTIBLE_MIMES, true)) {
            $tempPath = tempnam(sys_get_temp_dir(), 'webp_upload_').'.webp';

            try {
                $image = Image::load($file->getRealPath());

                $width = $image->getWidth();
                $height = $image->getHeight();
                if ($width > 2400 || $height > 2400) {
                    if ($width >= $height) {
                        $image->width(2400);
                    } else {
                        $image->height(2400);
                    }
                }

                $image->format('webp')->quality(85)->save($tempPath);

                return $folder
                    ->addMedia($tempPath)
                    ->usingName($nameWithoutExtension.'.webp')
                    ->usingFileName(Str::slug($nameWithoutExtension).'.webp')
                    ->withCustomProperties($customProperties)
                    ->toMediaCollection('medialibrary');
            } finally {
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            }
        }

        return $folder
            ->addMedia($file->getRealPath())
            ->usingName($originalName)
            ->usingFileName(Str::slug($nameWithoutExtension).'.'.$extension)
            ->withCustomProperties($customProperties)
            ->toMediaCollection('medialibrary');
    }
}
