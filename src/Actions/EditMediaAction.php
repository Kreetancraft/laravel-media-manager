<?php

namespace Kreetancraft\Media\Actions;

use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Image\Enums\FlipDirection;
use Spatie\Image\Enums\Orientation;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EditMediaAction
{
    use AsAction;

    public function __construct(
        private readonly MediaItemsContract $mediaItems,
    ) {}

    /**
     * Apply a set of non-destructive image transforms and persist the result
     * as a WebP variant, overwriting the media's existing auto conversion so
     * the gallery's `?webp=1` serving keeps working.
     *
     * @param  array{crop?: array{x: int, y: int, width: int, height: int}, rotate?: int, flip?: string, zoom?: float, brightness?: int, contrast?: float}  $edits
     */
    public function handle(Media $media, array $edits): string
    {
        if (! str_starts_with($media->mime_type, 'image/')) {
            throw new \InvalidArgumentException('Only image media can be edited.');
        }

        $sourcePath = $media->getPath();

        if (! file_exists($sourcePath)) {
            throw new \RuntimeException('Source image file not found.');
        }

        $edits = self::sanitize($edits);

        $image = Image::load($sourcePath);

        if (! empty($edits['rotate']) && (int) $edits['rotate'] !== 0) {
            $image->orientation($this->toOrientation((int) $edits['rotate']));
        }

        if (! empty($edits['flip'])) {
            $image->flip($this->toFlipDirection((string) $edits['flip']));
        }

        if (! empty($edits['zoom']) && (float) $edits['zoom'] !== 1.0) {
            $scale = (float) $edits['zoom'];
            $width = (int) round($image->getWidth() * $scale);
            $height = (int) round($image->getHeight() * $scale);
            $max = (int) config('media.editor.max_dimension', 6000);

            // The payload comes from the browser. Without this ceiling a
            // single request can ask for a hundred-megapixel allocation.
            if ($width > $max || $height > $max) {
                throw new \InvalidArgumentException(
                    "Requested size {$width}x{$height} exceeds the {$max}px limit."
                );
            }

            $image->resize($width, $height);
        }

        if (! empty($edits['crop']) && isset($edits['crop']['width'], $edits['crop']['height'])) {
            $width = (int) $edits['crop']['width'];
            $height = (int) $edits['crop']['height'];
            $x = isset($edits['crop']['x']) ? (int) $edits['crop']['x'] : (int) (($image->getWidth() - $width) / 2);
            $y = isset($edits['crop']['y']) ? (int) $edits['crop']['y'] : (int) (($image->getHeight() - $height) / 2);

            $image->manualCrop($width, $height, $x, $y);
        }

        if (isset($edits['brightness']) && (int) $edits['brightness'] !== 0) {
            $image->brightness((int) $edits['brightness']);
        }

        if (isset($edits['contrast']) && (float) $edits['contrast'] !== 0.0) {
            $image->contrast((float) $edits['contrast']);
        }

        $disk = Storage::disk($media->disk);
        $destinationRelative = "conversions/{$media->id}/auto.webp";
        $destinationAbsolute = $disk->path($destinationRelative);

        if (! $disk->exists(dirname($destinationRelative))) {
            $disk->makeDirectory(dirname($destinationRelative));
        }

        $image->format('webp')->quality(85)->save($destinationAbsolute);

        $media->setCustomProperty('webp_url', $disk->url($destinationRelative));
        $media->setCustomProperty('webp_path', $destinationRelative);
        $media->setCustomProperty('edit', $edits);
        $media->save();

        return $media->getCustomProperty('webp_url');
    }

    private function toOrientation(int $degrees): Orientation
    {
        return match ($degrees) {
            90 => Orientation::Rotate90,
            180 => Orientation::Rotate180,
            270 => Orientation::Rotate270,
            -90 => Orientation::RotateMinus90,
            default => throw new \InvalidArgumentException("Unsupported rotation: {$degrees}"),
        };
    }

    private function toFlipDirection(string $direction): FlipDirection
    {
        return match ($direction) {
            'horizontal' => FlipDirection::Horizontal,
            'vertical' => FlipDirection::Vertical,
            'both' => FlipDirection::Both,
            default => throw new \InvalidArgumentException("Unsupported flip direction: {$direction}"),
        };
    }

    /**
     * Clamp a client-supplied transform payload to sane bounds.
     *
     * Livewire action arguments are attacker-controlled, and the docblock above
     * documents a shape that nothing previously enforced.
     *
     * @param  array<string, mixed>  $edits
     * @return array<string, mixed>
     */
    private static function sanitize(array $edits): array
    {
        $clean = [];

        if (isset($edits['zoom'])) {
            $clean['zoom'] = max(
                (float) config('media.editor.min_zoom', 0.1),
                min((float) config('media.editor.max_zoom', 4.0), (float) $edits['zoom']),
            );
        }

        if (isset($edits['rotate'])) {
            $rotate = (int) $edits['rotate'];
            $clean['rotate'] = in_array($rotate, [0, 90, 180, 270], true) ? $rotate : 0;
        }

        if (isset($edits['flip']) && in_array($edits['flip'], ['horizontal', 'vertical', 'both'], true)) {
            $clean['flip'] = (string) $edits['flip'];
        }

        if (isset($edits['brightness'])) {
            $clean['brightness'] = max(-100, min(100, (int) $edits['brightness']));
        }

        if (isset($edits['contrast'])) {
            $clean['contrast'] = max(-100.0, min(100.0, (float) $edits['contrast']));
        }

        if (isset($edits['crop']) && is_array($edits['crop'])) {
            $max = (int) config('media.editor.max_dimension', 6000);
            $crop = [];
            foreach (['x', 'y', 'width', 'height'] as $key) {
                if (isset($edits['crop'][$key])) {
                    $crop[$key] = max(0, min($max, (int) $edits['crop'][$key]));
                }
            }
            if (isset($crop['width'], $crop['height']) && $crop['width'] > 0 && $crop['height'] > 0) {
                $clean['crop'] = $crop;
            }
        }

        return $clean;
    }
}
