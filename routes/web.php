<?php

use Illuminate\Support\Facades\Route;
use Kreetancraft\Media\Livewire\MediaEditor;
use Kreetancraft\Media\Livewire\MediaGallery;

/*
 * Prefix and middleware are applied by MediaServiceProvider::registerRoutes()
 * from config, so this file only declares the paths and names.
 */
$names = config('media.routes.names', []);
$ability = config('media.permission', 'manage-media');

Route::middleware("can:{$ability}")->group(function () use ($names): void {
    Route::get('media', MediaGallery::class)
        ->name($names['index'] ?? 'admin.media');

    Route::get('media/{mediaId}/edit', MediaEditor::class)
        ->name($names['edit'] ?? 'admin.media.edit');
});
