<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Concerns\HasMediaAttachments;
use Kreetancraft\Media\Models\MediaAttachment;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Minimal model that uses the trait, backed by the existing `folders` table so
 * no extra migration is needed for the test.
 */
class AttachableStub extends Model
{
    use HasMediaAttachments;

    protected $table = 'folders';

    protected $guarded = [];
}

beforeEach(function () {
    Storage::fake('public');
});

function freshLibraryMedia(string $name = 'pic.jpg'): Media
{
    $home = Folder::firstOrCreate(['slug' => 'home'], ['name' => 'Home', 'parent_id' => null]);
    $file = UploadedFile::fake()->image($name);
    $stored = Storage::disk('public')->putFileAs('tmp', $file, $name);

    return $home->addMedia(Storage::disk('public')->path($stored))->toMediaCollection('medialibrary');
}

function attachableOwner(): AttachableStub
{
    return AttachableStub::create(['name' => 'Owner', 'slug' => 'owner-'.uniqid()]);
}

test('attachMedia links a media item to the owner', function () {
    $owner = attachableOwner();
    $media = freshLibraryMedia();

    $owner->attachMedia($media->id, 'gallery');

    expect($owner->attachedMedia('gallery'))->toHaveCount(1)
        ->and($owner->attachedMedia('gallery')->first()->id)->toBe($media->id);
});

test('attachMedia is idempotent on the unique key', function () {
    $owner = attachableOwner();
    $media = freshLibraryMedia();

    $owner->attachMedia($media->id, 'gallery');
    $owner->attachMedia($media->id, 'gallery');

    expect($owner->mediaAttachments()->where('collection_name', 'gallery')->count())->toBe(1);
});

test('detachMedia removes a single attachment', function () {
    $owner = attachableOwner();
    $a = freshLibraryMedia('a.jpg');
    $b = freshLibraryMedia('b.jpg');
    $owner->attachMedia($a->id, 'gallery');
    $owner->attachMedia($b->id, 'gallery');

    $owner->detachMedia($a->id, 'gallery');

    expect($owner->attachedMedia('gallery')->pluck('id')->all())->toBe([$b->id]);
});

test('syncAttachedMedia replaces the collection in the given order', function () {
    $owner = attachableOwner();
    $a = freshLibraryMedia('a.jpg');
    $b = freshLibraryMedia('b.jpg');
    $c = freshLibraryMedia('c.jpg');

    $owner->attachMedia($a->id, 'gallery');
    $owner->syncAttachedMedia([$c->id, $b->id], 'gallery');

    expect($owner->attachedMedia('gallery')->pluck('id')->all())->toBe([$c->id, $b->id]);
});

test('deleting a media row cascades to its attachment rows', function () {
    $owner = attachableOwner();
    $media = freshLibraryMedia();
    $owner->attachMedia($media->id, 'gallery');

    $media->delete();

    expect($owner->mediaAttachments()->count())->toBe(0);
});

test('deleting the owner drops its attachments but keeps the shared media', function () {
    $owner = attachableOwner();
    $media = freshLibraryMedia();
    $owner->attachMedia($media->id, 'gallery');

    $owner->delete();

    expect(MediaAttachment::count())->toBe(0)
        ->and(Media::find($media->id))->not->toBeNull();
});

test('collections are isolated from one another', function () {
    $owner = attachableOwner();
    $a = freshLibraryMedia('a.jpg');
    $b = freshLibraryMedia('b.jpg');

    $owner->attachMedia($a->id, 'gallery');
    $owner->attachMedia($b->id, 'hero');

    expect($owner->attachedMedia('gallery')->pluck('id')->all())->toBe([$a->id])
        ->and($owner->attachedMedia('hero')->pluck('id')->all())->toBe([$b->id]);
});
