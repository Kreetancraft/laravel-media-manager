<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Kreetancraft\Media\Livewire\AvatarUploader;
use Kreetancraft\Media\Models\MediaAttachment;
use Kreetancraft\Media\Support\MediaImageResolver;
use Kreetancraft\Media\Tests\Fixtures\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Upload one image without opening the library.
 *
 * The picker browses every file and is gated on `viewAny` for media, so letting
 * someone set their own picture meant showing them everyone else's files and
 * granting a permission they have no other use for. This asks for a file, and
 * authorizes on the subject rather than on the library.
 */
beforeEach(function (): void {
    home(); // the root folder uploads land in
    $this->resolver = new MediaImageResolver;
});

it('uploads an image and attaches it', function (): void {
    $user = User::create(['name' => 'Me', 'email' => 'me@example.com', 'password' => 'x']);
    $this->actingAs($user);

    Livewire::test(AvatarUploader::class, ['model' => $user])
        ->set('upload', UploadedFile::fake()->image('face.jpg'))
        ->assertHasNoErrors();

    expect($this->resolver->urlFor($user->fresh(), 'avatar'))->not->toBeNull();
});

it('announces the upload the same way the picker announces a pick', function (): void {
    // So anything already listening for a chosen image does not care which way
    // it arrived.
    $user = User::create(['name' => 'Me', 'email' => 'me2@example.com', 'password' => 'x']);
    $this->actingAs($user);

    Livewire::test(AvatarUploader::class, ['model' => $user, 'group' => 'user-avatar-1'])
        ->set('upload', UploadedFile::fake()->image('face.jpg'))
        ->assertDispatched('media-picked');
});

it('lets someone set their own while the library itself is denied to them', function (): void {
    // The whole point. Creating any permission switches the media policy from
    // open to enforcing, so this user genuinely cannot browse the library — and
    // must still be able to change their own picture. Without that first line
    // the policy reads as open and the test proves nothing.
    Permission::findOrCreate('view-media', 'web');

    $user = User::create(['name' => 'Me', 'email' => 'me3@example.com', 'password' => 'x']);
    $this->actingAs($user);

    expect(Gate::allows('viewAny', MediaAttachment::class))->toBeFalse();

    Livewire::test(AvatarUploader::class, ['model' => $user])
        ->set('upload', UploadedFile::fake()->image('face.jpg'))
        ->assertHasNoErrors();

    expect($this->resolver->urlFor($user->fresh(), 'avatar'))->not->toBeNull();
});

it('refuses to set someone else\'s without permission to update them', function (): void {
    $me = User::create(['name' => 'Me', 'email' => 'me4@example.com', 'password' => 'x']);
    $someoneElse = User::create(['name' => 'Them', 'email' => 'them@example.com', 'password' => 'x']);

    Gate::define('update', fn ($user, $model) => false);

    $this->actingAs($me);

    Livewire::test(AvatarUploader::class, ['model' => $someoneElse])
        ->assertForbidden();
});

it('rejects a file that is not an image', function (): void {
    // The library accepts documents; a profile picture that turns out to be a
    // PDF is not a picture.
    $user = User::create(['name' => 'Me', 'email' => 'me5@example.com', 'password' => 'x']);
    $this->actingAs($user);

    Livewire::test(AvatarUploader::class, ['model' => $user])
        ->set('upload', UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'));

    expect($this->resolver->urlFor($user->fresh(), 'avatar'))->toBeNull();
});

it('removes the image again', function (): void {
    $user = User::create(['name' => 'Me', 'email' => 'me6@example.com', 'password' => 'x']);
    $this->actingAs($user);

    $component = Livewire::test(AvatarUploader::class, ['model' => $user])
        ->set('upload', UploadedFile::fake()->image('face.jpg'));

    expect($this->resolver->urlFor($user->fresh(), 'avatar'))->not->toBeNull();

    $component->call('remove');

    expect((new MediaImageResolver)->urlFor($user->fresh(), 'avatar'))->toBeNull();
});
