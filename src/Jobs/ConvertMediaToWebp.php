<?php

namespace Kreetancraft\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ConvertMediaToWebp implements ShouldQueue
{
    use FoundationQueueable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * Mime types we will convert to WebP. Other types (PDF, SVG, anything
     * already WebP) are skipped.
     */
    private const CONVERTIBLE_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
    ];

    /**
     * Responsive WebP variants generated alongside the full-size auto.webp.
     * Keys are the conversion names used by hasGeneratedConversion() and the
     * ?conversion= query param on the public assets route.
     *
     * @var array<string, array{width: int, height: int, fit: string}>
     */
    private const RESPONSIVE_SIZES = [
        'thumbnail' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
        'medium' => ['width' => 400, 'height' => 400, 'fit' => 'contain'],
        'large' => ['width' => 800, 'height' => 800, 'fit' => 'contain'],
    ];

    public function __construct(public readonly int $mediaId) {}

    public function handle(): void
    {
        $media = Media::find($this->mediaId);

        if ($media === null) {
            return;
        }

        if (! in_array($media->mime_type, self::CONVERTIBLE_MIMES, true)) {
            return;
        }

        $sourcePath = $media->getPath();

        if (! file_exists($sourcePath)) {
            return;
        }

        // Pre-flight check: skip files larger than 20 MB
        $fileSize = filesize($sourcePath);
        if ($fileSize > 20 * 1024 * 1024) {
            logger()->warning("ConvertMediaToWebp: Skipping WebP conversion because file is too large: {$sourcePath} (".round($fileSize / 1024 / 1024, 2).' MB)');

            return;
        }

        $disk = Storage::disk($media->disk);
        $destinationRelative = "conversions/{$media->id}/auto.webp";
        $destinationAbsolute = $disk->path($destinationRelative);

        // Ensure target directory exists on the disk.
        if (! $disk->exists(dirname($destinationRelative))) {
            $disk->makeDirectory(dirname($destinationRelative));
        }

        try {
            $image = Image::load($sourcePath);
            $width = $image->getWidth();
            $height = $image->getHeight();

            // Cap longest edge to 2400px
            if ($width > 2400 || $height > 2400) {
                if ($width >= $height) {
                    $image->width(2400);
                } else {
                    $image->height(2400);
                }
            }

            $image->format('webp')
                ->quality(85)
                ->save($destinationAbsolute);

            $media->setCustomProperty('webp_url', $disk->url($destinationRelative));
            $media->setCustomProperty('webp_path', $destinationRelative);

            $this->generateResponsiveSizes($media, $sourcePath, $disk);

            $media->save();
        } catch (\Throwable $e) {
            logger()->error("ConvertMediaToWebp: Failed to convert media ID {$media->id} ({$media->file_name}): ".$e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Generate the thumbnail / medium / large WebP variants. These are stored
     * in the standard conversions directory and recorded so the gallery can
     * serve them through the public assets route via ?conversion=<name>.
     */
    private function generateResponsiveSizes(Media $media, string $sourcePath, Filesystem $disk): void
    {
        $responsive = [
            'urls' => [],
            'paths' => [],
        ];

        foreach (self::RESPONSIVE_SIZES as $name => $config) {
            $destinationRelative = "conversions/{$media->id}/{$name}.webp";
            $destinationAbsolute = $disk->path($destinationRelative);

            try {
                $image = Image::load($sourcePath)
                    ->format('webp')
                    ->quality(85);

                if ($config['fit'] === 'crop') {
                    $image->fit(Fit::Crop, $config['width'], $config['height']);
                } else {
                    $image->fit(Fit::Contain, $config['width'], $config['height']);
                }

                $image->save($destinationAbsolute);

                $responsive['urls'][$name] = $disk->url($destinationRelative);
                $responsive['paths'][$name] = $destinationRelative;
                $media->markAsConversionGenerated($name);
            } catch (\Throwable $e) {
                logger()->error("ConvertMediaToWebp: Failed to generate '{$name}' size for media ID {$media->id}: ".$e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        }

        $media->setCustomProperty('responsive', $responsive);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        logger()->error("ConvertMediaToWebp Job Failed: Media ID {$this->mediaId}. Message: ".$exception->getMessage());
    }
}
