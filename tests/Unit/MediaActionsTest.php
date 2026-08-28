<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Kreetancraft\Media\Actions\CreateFolderAction;
use Kreetancraft\Media\Actions\DeleteFolderAction;
use Kreetancraft\Media\Actions\DeleteItemsAction;
use Kreetancraft\Media\Actions\DeleteMediaAction;
use Kreetancraft\Media\Actions\GenerateAltTextAction;
use Kreetancraft\Media\Actions\MoveItemsAction;
use Kreetancraft\Media\Actions\RenameFolderAction;
use Kreetancraft\Media\Actions\RenameMediaAction;
use Kreetancraft\Media\Actions\UpdateMediaMetadataAction;
use Kreetancraft\Media\Actions\UploadMediaAction;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Create the parent-less root (home) folder.
 */
function rootFolder(): Folder
{
    return Folder::create(['name' => 'Home', 'slug' => 'home', 'parent_id' => null]);
}

/**
 * Create a subfolder under the given parent.
 */
function subFolder(Folder $parent, string $name): Folder
{
    return Folder::create([
        'name' => $name,
        'slug' => Str::slug($name),
        'parent_id' => $parent->id,
    ]);
}

/**
 * Attach a fake image to a folder and return the Media row.
 */
function attachMedia(Folder $folder, string $fileName): Media
{
    $file = UploadedFile::fake()->image($fileName);
    $stored = Storage::disk('public')->putFileAs('tmp', $file, $fileName);

    return $folder->addMedia(Storage::disk('public')->path($stored))
        ->toMediaCollection('medialibrary');
}

test('CreateFolderAction creates a slugified folder under its parent', function () {
    $root = rootFolder();

    $folder = CreateFolderAction::run($root, '  Trek Photos  ');

    expect($folder->name)->toBe('Trek Photos')
        ->and($folder->slug)->toBe('trek-photos')
        ->and($folder->parent_id)->toBe($root->id);
});

test('RenameFolderAction updates the name and keeps the slug in sync', function () {
    $folder = subFolder(rootFolder(), 'Old Name');

    RenameFolderAction::run($folder, 'New Name');

    expect($folder->fresh()->name)->toBe('New Name')
        ->and($folder->fresh()->slug)->toBe('new-name');
});

test('DeleteFolderAction deletes a normal folder', function () {
    $folder = subFolder(rootFolder(), 'Delete Me');

    DeleteFolderAction::run($folder);

    expect(Folder::find($folder->id))->toBeNull();
});

test('DeleteFolderAction refuses to delete the root folder', function () {
    $root = rootFolder();

    expect(fn () => DeleteFolderAction::run($root))
        ->toThrow(RuntimeException::class);

    expect(Folder::find($root->id))->not->toBeNull();
});

test('RenameMediaAction preserves the original extension and slugifies the name', function () {
    $media = attachMedia(rootFolder(), 'original.jpg');

    RenameMediaAction::run($media, 'Sunny Beach');

    expect($media->fresh()->name)->toBe('Sunny Beach')
        ->and($media->fresh()->file_name)->toBe('sunny-beach.jpg');
});

test('DeleteMediaAction deletes the media item', function () {
    $root = rootFolder();
    $media = attachMedia($root, 'gone.jpg');

    DeleteMediaAction::run($media);

    expect($root->fresh()->getMedia('medialibrary')->count())->toBe(0);
});

test('DeleteItemsAction bulk deletes folders and files but skips the root', function () {
    $root = rootFolder();
    $sub = subFolder($root, 'Removable');
    $media = attachMedia($root, 'bulk.jpg');

    DeleteItemsAction::run([$root->id, $sub->id], [$media->id]);

    expect(Folder::find($root->id))->not->toBeNull()
        ->and(Folder::find($sub->id))->toBeNull()
        ->and(Media::find($media->id))->toBeNull();
});

test('MoveItemsAction moves a folder into the target folder', function () {
    $root = rootFolder();
    $source = subFolder($root, 'Source');
    $target = subFolder($root, 'Target');

    MoveItemsAction::run([$source->id], [], $target->id);

    expect($source->fresh()->parent_id)->toBe($target->id);
});

test('MoveItemsAction skips a circular move into its own descendant', function () {
    $root = rootFolder();
    $parent = subFolder($root, 'Parent');
    $child = subFolder($parent, 'Child');

    MoveItemsAction::run([$parent->id], [], $child->id);

    // Parent must not become a child of its own descendant.
    expect($parent->fresh()->parent_id)->toBe($root->id);
});

test('MoveItemsAction never moves the root folder', function () {
    $root = rootFolder();
    $target = subFolder($root, 'Target');

    MoveItemsAction::run([$root->id], [], $target->id);

    expect($root->fresh()->parent_id)->toBeNull();
});

test('UpdateMediaMetadataAction stores title on the name column and others as custom properties', function () {
    $media = attachMedia(rootFolder(), 'meta.jpg');

    UpdateMediaMetadataAction::run($media, 'title', '  My Title  ');
    UpdateMediaMetadataAction::run($media, 'caption', 'A caption');

    expect($media->fresh()->name)->toBe('My Title')
        ->and($media->fresh()->getCustomProperty('caption'))->toBe('A caption');
});

test('GenerateAltTextAction produces Nepal-aware alt text and persists it', function () {
    $media = attachMedia(rootFolder(), 'trek-annapurna.jpg');

    $altText = GenerateAltTextAction::run($media);

    expect($altText)->toBe('An image showing Trek Annapurna during a trekking adventure in the Himalayas, Nepal')
        ->and($media->fresh()->getCustomProperty('alt_text'))->toBe($altText);
});

test('UploadMediaAction transcodes a JPEG upload to a WebP variant', function () {
    $root = rootFolder();

    $media = UploadMediaAction::run($root, UploadedFile::fake()->image('holiday.jpg'), null);

    expect($media->file_name)->toBe('holiday.webp')
        ->and($media->collection_name)->toBe('medialibrary');
});

test('UploadMediaAction stores non-image files as-is with a slugified name', function () {
    $root = rootFolder();

    $media = UploadMediaAction::run($root, UploadedFile::fake()->create('My Itinerary.pdf', 100, 'application/pdf'), null);

    expect($media->file_name)->toBe('my-itinerary.pdf');
});
