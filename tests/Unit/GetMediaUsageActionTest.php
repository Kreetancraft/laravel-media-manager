<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Kreetancraft\Media\Actions\GetMediaUsageAction;
use Kreetancraft\Media\Models\MediaAttachment;
use Kreetancraft\Media\Tests\Fixtures\Models\User;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Event::fake([MediaHasBeenAddedEvent::class]);
});

function createLibraryMedia(): Media
{
    $folder = Folder::create(['name' => 'Usage Folder', 'slug' => 'usage-folder']);
    $media = $folder->addMedia(
        UploadedFile::fake()->image('usage.jpg', 400, 400)
    )->toMediaCollection('medialibrary');
    $media->mime_type = 'image/jpeg';
    $media->save();

    return $media;
}

test('usage is empty when media is not attached anywhere', function () {
    $media = createLibraryMedia();

    expect((new GetMediaUsageAction)($media))->toBe([]);
});

test('usage lists every model attached to the media', function () {
    $media = createLibraryMedia();
    $userA = User::factory()->create(['name' => 'Alice']);
    $userB = User::factory()->create(['name' => 'Bob']);

    MediaAttachment::create([
        'attachable_type' => $userA::class,
        'attachable_id' => $userA->id,
        'media_id' => $media->id,
        'collection_name' => 'avatar',
    ]);
    MediaAttachment::create([
        'attachable_type' => $userB::class,
        'attachable_id' => $userB->id,
        'media_id' => $media->id,
        'collection_name' => 'default',
    ]);

    $usage = (new GetMediaUsageAction)($media);

    expect($usage)->toHaveCount(2)
        ->and($usage[0])->toHaveKeys(['type', 'id', 'title', 'collection', 'url'])
        ->and($usage[0]['type'])->toBe('User')
        ->and($usage[0]['title'])->toBe('Alice')
        ->and($usage[0]['collection'])->toBe('avatar')
        ->and($usage[1]['title'])->toBe('Bob');
});
