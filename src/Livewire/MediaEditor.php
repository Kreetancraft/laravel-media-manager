<?php

namespace Kreetancraft\Media\Livewire;

use Flux\Flux;
use Kreetancraft\Media\Actions\EditMediaAction;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaEditor extends Component
{
    public ?int $mediaId = null;

    public ?Media $media = null;

    /**
     * Original (untransformed) pixel dimensions, used by the canvas preview.
     */
    public int $origWidth = 0;

    public int $origHeight = 0;

    /**
     * Rotation in degrees (90 / 180 / 270 / -90).
     */
    public int $rotate = 0;

    /**
     * Flip direction: '', 'horizontal', 'vertical', 'both'.
     */
    public string $flip = '';

    /**
     * Brightness adjustment (-100 .. 100).
     */
    public int $brightness = 0;

    /**
     * Contrast adjustment (-100 .. 100).
     */
    public float $contrast = 0;

    /**
     * Zoom scale applied before cropping (0.5 .. 3).
     */
    public float $zoom = 1.0;

    /**
     * Crop rectangle in source pixel coordinates (post rotate/zoom space).
     *
     * @var array{x: int, y: int, width: int, height: int}
     */
    public array $crop = [];

    public function mount(int $mediaId): void
    {
        $this->authorize('manage-media');

        $this->media = app(MediaItemsContract::class)->find($mediaId);

        if ($this->media === null) {
            abort(404);
        }

        if (! str_starts_with($this->media->mime_type, 'image/')) {
            abort(404, __('Only images can be edited.'));
        }

        $path = $this->media->getPath();
        if (file_exists($path)) {
            [$this->origWidth, $this->origHeight] = getimagesize($path);
        }

        // Seed controls from any previously stored edit.
        $previous = $this->media->getCustomProperty('edit', []);
        $this->rotate = (int) ($previous['rotate'] ?? 0);
        $this->flip = (string) ($previous['flip'] ?? '');
        $this->brightness = (int) ($previous['brightness'] ?? 0);
        $this->contrast = (float) ($previous['contrast'] ?? 0);
        $this->zoom = (float) ($previous['zoom'] ?? 1.0);
        $this->crop = (array) ($previous['crop'] ?? []);
    }

    /**
     * Apply the accumulated transforms and persist the WebP variant.
     *
     * The editor UI builds the transform payload client-side and passes it
     * here; when called without arguments (e.g. tests) the public properties
     * are used instead.
     *
     * @param  array<string, mixed>  $edits
     */
    public function save(array $edits = []): void
    {
        $this->authorize('manage-media');

        if ($edits === []) {
            $edits = array_filter([
                'rotate' => $this->rotate !== 0 ? $this->rotate : null,
                'flip' => $this->flip !== '' ? $this->flip : null,
                'brightness' => $this->brightness !== 0 ? $this->brightness : null,
                'contrast' => $this->contrast !== 0.0 ? $this->contrast : null,
                'zoom' => $this->zoom !== 1.0 ? $this->zoom : null,
                'crop' => $this->crop !== [] ? $this->crop : null,
            ]);
        }

        EditMediaAction::run($this->media, $edits);

        Flux::toast(variant: 'success', text: __('Image edited successfully.'));
        $this->dispatch('media-edited', id: $this->media->id);

        $this->redirect(route(config('media.routes.names.index', 'admin.media')), navigate: true);
    }

    #[Title('Edit Image - Admin')]
    public function render()
    {
        return view('media::livewire.editor', [
            'sourceUrl' => $this->media->getUrl(),
        ])->layout(config('media.layouts.admin', 'components.layouts.app'));
    }
}
