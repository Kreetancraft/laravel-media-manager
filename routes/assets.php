<?php

use Illuminate\Support\Facades\Route;
use Kreetancraft\Media\Http\Controllers\FileController;

/*
|--------------------------------------------------------------------------
| Public asset serving
|--------------------------------------------------------------------------
|
| Serves a file by its exact folder path, WordPress-style. Deliberately outside
| the admin group: these URLs appear in public pages, so they carry no auth.
| FileController 404s on anything but an exact folder-path + filename match, so
| there is no enumerable listing surface.
|
*/

Route::get(
    trim((string) config('media.routes.asset_prefix', 'assets'), '/').'/{path}',
    [FileController::class, 'show']
)
    ->where('path', '.*')
    ->name(config('media.routes.names.asset', 'media.asset'));
