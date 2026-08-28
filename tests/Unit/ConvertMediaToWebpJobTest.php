<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Jobs\ConvertMediaToWebp;
use Kreetancraft\Media\Tests\Fixtures\Models\User;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Persist a fake upload to disk so Spatie's addMedia(path) gets a stable path.
 */
function persistFakeUpload(string $name, string $mime = 'image/jpeg', int $width = 400, int $height = 400): string
{
    $upload = $mime === 'application/pdf'
        ? UploadedFile::fake()->create($name, 10, $mime)
        : UploadedFile::fake()->image($name, $width, $height);

    $stored = Storage::disk('public')->putFileAs('tmp', $upload, $name);

    return Storage::disk('public')->path($stored);
}

test('job creates a webp variant for a jpg upload', function () {
    // Invoke the job manually; stop the listener from also firing.
    Event::fake([MediaHasBeenAddedEvent::class]);

    $user = User::factory()->create();
    $user->addMedia(persistFakeUpload('source.jpg'))->toMediaCollection('profile_picture');
    $media = $user->getFirstMedia('profile_picture');

    ConvertMediaToWebp::dispatchSync($media->id);

    $media->refresh();
    $webpPath = $media->getCustomProperty('webp_path');

    expect($webpPath)->toBeString()->toEndWith('.webp')
        ->and(Storage::disk('public')->exists($webpPath))->toBeTrue()
        ->and($media->getCustomProperty('webp_url'))->toBeString()->toEndWith('.webp');
});

test('job is a no-op for an already-webp file', function () {
    Event::fake([MediaHasBeenAddedEvent::class]);

    $user = User::factory()->create();
    $user->addMedia(persistFakeUpload('logo.webp'))->toMediaCollection('profile_picture');
    $media = $user->getFirstMedia('profile_picture');
    $media->mime_type = 'image/webp';
    $media->save();

    ConvertMediaToWebp::dispatchSync($media->id);

    expect($media->fresh()->getCustomProperty('webp_url'))->toBeNull();
});

test('job is a no-op when the media row is missing', function () {
    ConvertMediaToWebp::dispatchSync(999999);

    expect(true)->toBeTrue();
});

test('job is a no-op when the source file is larger than 20 MB', function () {
    Event::fake([MediaHasBeenAddedEvent::class]);

    $folder = Folder::create(['name' => 'Test Folder', 'slug' => 'test-folder']);
    $media = $folder->addMedia(persistFakeUpload('big_file.jpg'))->toMediaCollection('medialibrary');
    $media->mime_type = 'image/jpeg';
    $media->save();

    // Overwrite the stored file with a 21 MB sparse file.
    $fp = fopen($media->getPath(), 'w');
    fseek($fp, 21 * 1024 * 1024 - 1);
    fwrite($fp, "\0");
    fclose($fp);

    ConvertMediaToWebp::dispatchSync($media->id);

    expect($media->fresh()->getCustomProperty('webp_url'))->toBeNull();
});

test('job generates responsive thumbnail, medium and large webp sizes', function () {
    Event::fake([MediaHasBeenAddedEvent::class]);

    $folder = Folder::create(['name' => 'Responsive Folder', 'slug' => 'responsive-folder']);
    $media = $folder->addMedia(persistFakeUpload('responsive.jpg', 'image/jpeg', 1200, 800))
        ->toMediaCollection('medialibrary');
    $media->mime_type = 'image/jpeg';
    $media->save();

    ConvertMediaToWebp::dispatchSync($media->id);

    $media->refresh();
    $responsive = $media->getCustomProperty('responsive');

    foreach (['thumbnail', 'medium', 'large'] as $name) {
        $path = $responsive['paths'][$name] ?? null;

        expect($path)->toBeString()->toEndWith("{$name}.webp")
            ->and(Storage::disk('public')->exists($path))->toBeTrue()
            ->and($media->hasGeneratedConversion($name))->toBeTrue()
            ->and($media->getCustomProperty("responsive.urls.{$name}"))->toBeString()->toEndWith("{$name}.webp");
    }
});

test('job is a no-op for responsive sizes on non-image media', function () {
    Event::fake([MediaHasBeenAddedEvent::class]);

    $folder = Folder::create(['name' => 'Pdf Folder', 'slug' => 'pdf-folder']);
    $media = $folder->addMedia(persistFakeUpload('doc.pdf', 'application/pdf'))->toMediaCollection('medialibrary');
    $media->mime_type = 'application/pdf';
    $media->save();

    ConvertMediaToWebp::dispatchSync($media->id);

    expect($media->fresh()->getCustomProperty('responsive'))->toBeNull();
});

test('job throws and writes no variant when the image cannot be decoded', function () {
    Event::fake([MediaHasBeenAddedEvent::class]);

    $folder = Folder::create(['name' => 'Test Folder', 'slug' => 'test-folder']);
    $media = $folder->addMedia(persistFakeUpload('corrupt.jpg'))->toMediaCollection('medialibrary');
    $media->mime_type = 'image/jpeg';
    $media->save();

    file_put_contents($media->getPath(), 'not an image file');

    $threw = false;
    try {
        ConvertMediaToWebp::dispatchSync($media->id);
    } catch (Throwable) {
        $threw = true;
    }

    expect($threw)->toBeTrue()
        ->and($media->fresh()->getCustomProperty('webp_url'))->toBeNull();
});
