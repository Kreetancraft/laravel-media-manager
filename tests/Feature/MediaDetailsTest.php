<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Livewire\MediaDetails;
use Kreetancraft\Media\Models\MediaAttachment;
use Kreetancraft\Media\Tests\Fixtures\Models\User;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    seedRolesAndPermissions();
    Storage::fake('public');
    Event::fake([MediaHasBeenAddedEvent::class]);
});

test('the details component loads media metadata on mount', function () {
    actingAsSuperAdmin();

    $media = storeFile(home(), 'details.jpg');
    $media->setCustomProperty('alt_text', 'Existing alt');
    $media->save();

    Livewire::test(MediaDetails::class, ['mediaId' => $media->id])
        ->assertSet('file.name', $media->name)
        ->assertSet('metadata.alt_text', 'Existing alt')
        ->assertSet('file.isImage', true);
});

test('the details component lists usage from the attachments pivot', function () {
    actingAsSuperAdmin();

    $media = storeFile(home(), 'used.jpg');
    $user = User::factory()->create(['name' => 'Carol']);
    MediaAttachment::create([
        'attachable_type' => $user::class,
        'attachable_id' => $user->id,
        'media_id' => $media->id,
        'collection_name' => 'avatar',
    ]);

    Livewire::test(MediaDetails::class, ['mediaId' => $media->id])
        ->assertSet('usage', fn ($usage) => count($usage) === 1
            && $usage[0]['type'] === 'User'
            && $usage[0]['title'] === 'Carol');
});

test('copy url dispatches the clipboard event', function () {
    actingAsSuperAdmin();

    $media = storeFile(home(), 'copy.jpg');

    Livewire::test(MediaDetails::class, ['mediaId' => $media->id])
        ->call('copyUrl')
        ->assertDispatched('clipboard-copy');
});

test('deleting the current media removes the row and notifies the gallery', function () {
    actingAsSuperAdmin();

    $media = storeFile(home(), 'delete-me.jpg');
    $id = $media->id;

    Livewire::test(MediaDetails::class, ['mediaId' => $media->id])
        ->call('deleteCurrent')
        ->assertDispatched('media-deleted', id: $id);

    expect(Media::find($id))->toBeNull();
});
