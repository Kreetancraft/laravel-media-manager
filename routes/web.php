<?php

use Illuminate\Support\Facades\Route;
use Kreetancraft\Media\Livewire\MediaEditor;
use Kreetancraft\Media\Livewire\MediaGallery;
use Kreetancraft\Media\Models\MediaAttachment;

/*
 * Prefix and middleware come from config via MediaServiceProvider; this file
 * only declares paths and names.
 *
 * The gate check is the ordinary policy form — ability plus model — not a
 * permission string. This package names no permissions; whatever policy is bound
 * to MediaAttachment decides, and a host can replace it wholesale.
 */
$names = config('media.routes.names', []);

Route::middleware('can:viewAny,'.MediaAttachment::class)->group(function () use ($names): void {
    Route::get('media', MediaGallery::class)
        ->name($names['index'] ?? 'admin.media');

    Route::get('media/{mediaId}/edit', MediaEditor::class)
        ->name($names['edit'] ?? 'admin.media.edit');
});
