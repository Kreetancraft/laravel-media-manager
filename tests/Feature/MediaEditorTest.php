<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Livewire\MediaEditor;
use Livewire\Livewire;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    seedRolesAndPermissions();
    Storage::fake('public');
});

function editorImage(string $name, int $width = 400, int $height = 300): Media
{
    $stored = Storage::disk('public')->putFileAs(
        'tmp',
        UploadedFile::fake()->image($name, $width, $height),
        $name,
    );

    $folder = Folder::create(['name' => 'Editor', 'slug' => 'editor']);
    $media = $folder->addMedia(Storage::disk('public')->path($stored))->toMediaCollection('medialibrary');
    $media->mime_type = 'image/jpeg';
    $media->save();

    return $media;
}

test('the editor mounts an image and exposes its dimensions', function () {
    actingAsSuperAdmin();

    $media = editorImage('mount.jpg', 400, 300);

    Livewire::test(MediaEditor::class, ['mediaId' => $media->id])
        ->assertSet('origWidth', 400)
        ->assertSet('origHeight', 300);
});

test('saving applies rotate and brightness through the edit action', function () {
    actingAsSuperAdmin();

    $media = editorImage('save.jpg', 400, 300);

    Livewire::test(MediaEditor::class, ['mediaId' => $media->id])
        ->set('rotate', 90)
        ->set('brightness', 15)
        ->call('save')
        ->assertHasNoErrors();

    $media->refresh();
    $edit = $media->getCustomProperty('edit');

    expect($edit)->toHaveKey('rotate', 90)
        ->and($edit)->toHaveKey('brightness', 15)
        ->and($media->getCustomProperty('webp_path'))->toEndWith('.webp');
});

test('saving a crop stores the crop rectangle', function () {
    actingAsSuperAdmin();

    $media = editorImage('crop.jpg', 400, 300);

    Livewire::test(MediaEditor::class, ['mediaId' => $media->id])
        ->set('crop', ['x' => 10, 'y' => 10, 'width' => 200, 'height' => 150])
        ->call('save')
        ->assertHasNoErrors();

    expect(Media::find($media->id)->getCustomProperty('edit.crop'))
        ->toBe(['x' => 10, 'y' => 10, 'width' => 200, 'height' => 150]);
});
