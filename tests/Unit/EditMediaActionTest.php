<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Actions\EditMediaAction;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Persist a fake image upload to a folder's medialibrary collection.
 */
function persistImage(string $name, int $width = 400, int $height = 300): Media
{
    $stored = Storage::disk('public')->putFileAs(
        'tmp',
        UploadedFile::fake()->image($name, $width, $height),
        $name,
    );

    $folder = Folder::create(['name' => 'Edit', 'slug' => 'edit']);
    $media = $folder->addMedia(Storage::disk('public')->path($stored))->toMediaCollection('medialibrary');
    $media->mime_type = 'image/jpeg';
    $media->save();

    return $media;
}

test('editing applies rotation and stores a webp variant', function () {
    $media = persistImage('rotate.jpg', 400, 300);

    $url = EditMediaAction::run($media, ['rotate' => 90]);

    $media->refresh();
    $webpPath = $media->getCustomProperty('webp_path');

    expect($url)->toBeString()->toEndWith('.webp')
        ->and(Storage::disk('public')->exists($webpPath))->toBeTrue();
});

test('editing rejects non-image media', function () {
    $stored = Storage::disk('public')->putFileAs(
        'tmp',
        UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        'doc.pdf',
    );
    $folder = Folder::create(['name' => 'Pdf', 'slug' => 'pdf']);
    $media = $folder->addMedia(Storage::disk('public')->path($stored))->toMediaCollection('medialibrary');
    $media->mime_type = 'application/pdf';
    $media->save();

    expect(fn () => EditMediaAction::run($media, ['rotate' => 90]))
        ->toThrow(InvalidArgumentException::class);
});

test('editing with a crop shrinks the resulting image dimensions', function () {
    $media = persistImage('crop.jpg', 400, 300);

    EditMediaAction::run($media, [
        'crop' => ['x' => 0, 'y' => 0, 'width' => 200, 'height' => 150],
    ]);

    $edited = Storage::disk('public')->path($media->fresh()->getCustomProperty('webp_path'));
    [$w, $h] = getimagesize($edited);

    expect($w)->toBe(200)->and($h)->toBe(150);
});

test('editing records the applied transform on the media', function () {
    $media = persistImage('meta.jpg', 400, 300);

    EditMediaAction::run($media, ['brightness' => 20, 'flip' => 'horizontal']);

    // Transforms are recorded in a canonical order by EditMediaAction::sanitize(),
    // so assert on content rather than the order the caller happened to use.
    expect($media->fresh()->getCustomProperty('edit'))
        ->toEqualCanonicalizing(['brightness' => 20, 'flip' => 'horizontal']);
});
