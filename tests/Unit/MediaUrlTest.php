<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Support\MediaUrl;
use LivewireFilemanager\Filemanager\Models\Folder;

beforeEach(function () {
    Storage::fake('public');
});

function urlMedia(string $name = 'pic.jpg', ?Folder $folder = null)
{
    $folder ??= Folder::firstOrCreate(['slug' => 'home'], ['name' => 'Home', 'parent_id' => null]);
    $upload = UploadedFile::fake()->image($name);
    $stored = Storage::disk('public')->putFileAs('tmp', $upload, $name);

    return $folder->addMedia(Storage::disk('public')->path($stored))->toMediaCollection('medialibrary');
}

test('publicFor returns the assets.show route for a media in a nested folder', function () {
    $home = Folder::firstOrCreate(['slug' => 'home'], ['name' => 'Home', 'parent_id' => null]);
    $trips = Folder::create(['parent_id' => $home->id, 'name' => 'trips', 'slug' => 'trips']);
    $media = urlMedia('photo.jpg', $trips);

    $url = MediaUrl::publicFor($media);

    expect($url)->toContain('/assets/trips/photo.jpg');
});

test('publicFor builds a path even when the file is in the home folder', function () {
    $media = urlMedia('flag.jpg');

    $url = MediaUrl::publicFor($media);

    expect($url)->not->toContain('/storage/');
});

test('publicFor walks deeper folder chains', function () {
    $home = Folder::firstOrCreate(['slug' => 'home'], ['name' => 'Home', 'parent_id' => null]);
    $countries = Folder::create(['parent_id' => $home->id, 'name' => 'countries', 'slug' => 'countries']);
    $nepal = Folder::create(['parent_id' => $countries->id, 'name' => 'nepal', 'slug' => 'nepal']);
    $media = urlMedia('flag.jpg', $nepal);

    $url = MediaUrl::publicFor($media);

    expect($url)->toContain('/assets/countries/nepal/flag.jpg');
});

test('publicFor appends the conversion query for a generated conversion', function () {
    $home = Folder::firstOrCreate(['slug' => 'home'], ['name' => 'Home', 'parent_id' => null]);
    $trips = Folder::create(['parent_id' => $home->id, 'name' => 'trips', 'slug' => 'trips']);
    $media = urlMedia('photo.jpg', $trips);
    $media->markAsConversionGenerated('thumbnail');

    $url = MediaUrl::publicFor($media, 'thumbnail');

    expect($url)
        ->toContain('/assets/trips/photo.jpg')
        ->toContain('conversion=thumbnail')
        ->not->toContain('/storage/');
});

test('publicFor ignores a conversion that was never generated', function () {
    $home = Folder::firstOrCreate(['slug' => 'home'], ['name' => 'Home', 'parent_id' => null]);
    $trips = Folder::create(['parent_id' => $home->id, 'name' => 'trips', 'slug' => 'trips']);
    $media = urlMedia('photo.jpg', $trips);

    $url = MediaUrl::publicFor($media, 'nonexistent');

    expect($url)
        ->toContain('/assets/trips/photo.jpg')
        ->not->toContain('conversion=');
});
