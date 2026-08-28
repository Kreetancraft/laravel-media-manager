<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Contracts\MediaContract;
use Kreetancraft\Media\Jobs\ConvertMediaToWebp;
use Kreetancraft\Media\Tests\Fixtures\Models\User;

beforeEach(function () {
    Storage::fake('public');
});

test('adding a jpg to a media collection dispatches the WebP conversion job', function () {
    Bus::fake();

    $user = User::factory()->create();
    $upload = UploadedFile::fake()->image('headshot.jpg', 400, 400);
    $stored = Storage::disk('public')->putFileAs('tmp', $upload, 'headshot.jpg');

    $user->addMedia(Storage::disk('public')->path($stored))->toMediaCollection('profile_picture');

    Bus::assertDispatched(ConvertMediaToWebp::class);
});

test('the full pipeline exposes a webp variant through MediaContract', function () {
    $user = User::factory()->create();

    $upload = UploadedFile::fake()->image('me.png', 400, 400);
    $stored = Storage::disk('public')->putFileAs('tmp', $upload, 'me.png');

    $user->addMedia(Storage::disk('public')->path($stored))->toMediaCollection('profile_picture');

    $media = $user->getFirstMedia('profile_picture')->refresh();

    expect(app(MediaContract::class)->webpUrlFor($media))
        ->toBeString()->not->toBeEmpty()->toEndWith('.webp');
});
