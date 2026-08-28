<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Livewire\MediaDetails;
use Kreetancraft\Media\Livewire\MediaGallery;
use Livewire\Livewire;
use LivewireFilemanager\Filemanager\Models\Folder;

beforeEach(function () {
    seedRolesAndPermissions();
    Storage::fake('public');
});

test('the gallery shows an empty state for a fresh root folder', function () {
    actingAsSuperAdmin();

    Livewire::test(MediaGallery::class)
        ->assertSee(__('No files or folders found'))
        ->assertSee(__('This directory is currently empty.'));
});

test('a super admin can create a subfolder', function () {
    actingAsSuperAdmin();

    Livewire::test(MediaGallery::class)
        ->set('newFolderName', 'Trek Photos')
        ->call('createFolder')
        ->assertHasNoErrors();

    $subfolder = Folder::where('name', 'Trek Photos')->first();

    expect($subfolder)->not->toBeNull()
        ->and($subfolder->parent_id)->toBe(home()->id);
});

test('creating a duplicate subfolder in the same directory is rejected', function () {
    actingAsSuperAdmin();

    Folder::create(['name' => 'Trek Photos', 'slug' => 'trek-photos', 'parent_id' => home()->id]);

    Livewire::test(MediaGallery::class)
        ->set('newFolderName', 'Trek Photos')
        ->call('createFolder')
        ->assertHasErrors(['newFolderName']);
});

test('a super admin can navigate into a subfolder', function () {
    actingAsSuperAdmin();

    $subfolder = Folder::create(['name' => 'Sub', 'slug' => 'sub', 'parent_id' => home()->id]);

    Livewire::test(MediaGallery::class)
        ->call('navigateToFolder', $subfolder->id)
        ->assertSet('currentFolder.id', $subfolder->id);
});

test('a super admin can rename a subfolder', function () {
    actingAsSuperAdmin();

    $subfolder = Folder::create(['name' => 'Old Name', 'slug' => 'old-name', 'parent_id' => home()->id]);

    Livewire::test(MediaGallery::class)
        ->set('selectedFolderId', $subfolder->id)
        ->set('editingName', 'New Name')
        ->call('renameFolder')
        ->assertHasNoErrors();

    expect($subfolder->fresh()->name)->toBe('New Name');
});

test('a super admin can delete a subfolder', function () {
    actingAsSuperAdmin();

    $subfolder = Folder::create(['name' => 'Delete Me', 'slug' => 'delete-me', 'parent_id' => home()->id]);

    Livewire::test(MediaGallery::class)
        ->call('deleteFolder', $subfolder->id)
        ->assertHasNoErrors();

    expect(Folder::find($subfolder->id))->toBeNull();
});

test('the root folder cannot be deleted', function () {
    actingAsSuperAdmin();

    $root = home();

    Livewire::test(MediaGallery::class)->call('deleteFolder', $root->id);

    expect(Folder::find($root->id))->not->toBeNull();
});

test('uploading images stores them as WebP variants', function () {
    actingAsSuperAdmin();

    Livewire::test(MediaGallery::class)
        ->set('uploads', [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.png'),
        ])
        ->assertHasNoErrors();

    $media = home()->getMedia('medialibrary');

    expect($media)->toHaveCount(2)
        ->and($media->pluck('file_name')->all())->toEqual(['photo1.webp', 'photo2.webp']);
});

test('a super admin can delete a file', function () {
    actingAsSuperAdmin();

    $media = storeFile(home(), 'delete.jpg');

    Livewire::test(MediaGallery::class)
        ->call('deleteFile', $media->id)
        ->assertHasNoErrors();

    expect(home()->fresh()->getMedia('medialibrary'))->toHaveCount(0);
});

test('the gallery paginates files at 18 per page', function () {
    actingAsSuperAdmin();

    for ($i = 1; $i <= 20; $i++) {
        storeFile(home(), "photo{$i}.jpg");
    }

    Livewire::test(MediaGallery::class)
        ->assertViewHas('files', fn ($files) => $files->count() === 18 && $files->total() === 20);
});

test('the gallery filters by file type', function () {
    actingAsSuperAdmin();

    storeFile(home(), 'image-one.jpg');
    storeFile(home(), 'image-two.png');
    $pdf = storeFile(home(), 'itinerary.pdf', 'application/pdf');
    $pdf->mime_type = 'application/pdf';
    $pdf->save();

    Livewire::test(MediaGallery::class)
        ->set('filterType', 'images')
        ->assertViewHas('files', fn ($files) => $files->count() === 2)
        ->set('filterType', 'documents')
        ->assertViewHas('files', fn ($files) => $files->count() === 1);
});

test('editing metadata persists to the media custom properties', function () {
    actingAsSuperAdmin();

    $media = storeFile(home(), 'meta.jpg');

    Livewire::test(MediaDetails::class, ['mediaId' => $media->id])
        ->set('metadata.caption', 'Beautiful Annapurna view');

    expect($media->fresh()->getCustomProperty('caption'))->toBe('Beautiful Annapurna view');
});

test('alt text generation produces Nepal-aware text', function () {
    actingAsSuperAdmin();

    $media = storeFile(home(), 'trek-annapurna.jpg');

    Livewire::test(MediaDetails::class, ['mediaId' => $media->id])
        ->call('generateAltText')
        ->assertSet('metadata.alt_text', 'An image showing Trek Annapurna during a trekking adventure in the Himalayas, Nepal');
});

test('adjacent navigation moves selection to the next media item', function () {
    actingAsSuperAdmin();

    $first = storeFile(home(), 'first.jpg');
    $second = storeFile(home(), 'second.jpg');

    Livewire::test(MediaDetails::class, ['mediaId' => $first->id, 'mediaIds' => [$first->id, $second->id]])
        ->call('navigateToAdjacentMedia', 'next')
        ->assertSet('mediaId', $second->id);
});

test('a super admin can bulk delete selected folders and files', function () {
    actingAsSuperAdmin();

    $folder = Folder::create(['name' => 'Bulk', 'slug' => 'bulk', 'parent_id' => home()->id]);
    $media = storeFile(home(), 'bulk.jpg');

    Livewire::test(MediaGallery::class)
        ->set('selectedFolders', [$folder->id])
        ->set('selectedFiles', [$media->id])
        ->call('deleteSelected');

    expect(Folder::find($folder->id))->toBeNull()
        ->and(home()->fresh()->getMedia('medialibrary'))->toHaveCount(0);
});

test('a super admin can move selected items into another folder', function () {
    actingAsSuperAdmin();

    $source = Folder::create(['name' => 'Source', 'slug' => 'source', 'parent_id' => home()->id]);
    $target = Folder::create(['name' => 'Target', 'slug' => 'target', 'parent_id' => home()->id]);

    Livewire::test(MediaGallery::class)
        ->set('selectedFolders', [$source->id])
        ->set('moveTargetFolderId', $target->id)
        ->call('moveSelected')
        ->assertHasNoErrors();

    expect($source->fresh()->parent_id)->toBe($target->id);
});
