<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permission
    |--------------------------------------------------------------------------
    |
    | The ability MediaPolicy checks. If your user model cannot answer can()
    | — no authorization package installed — every authenticated user is
    | allowed, so the library still works out of the box.
    |
    */
    'permission' => 'manage-media',

    /*
    |--------------------------------------------------------------------------
    | Layouts
    |--------------------------------------------------------------------------
    |
    | This package ships no layouts and no CSS. Its screens render into YOUR
    | layout and inherit your Tailwind/Flux theme. The default matches a stock
    | Laravel starter kit; point it at whatever your app actually uses.
    |
    */
    'layouts' => [
        'admin' => 'components.layouts.app',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Set `register` to false to keep the Livewire components (the picker in
    | particular) without mounting any of this package's own routes.
    |
    | `names.asset` is the route that serves folder-scoped files. If it does not
    | exist, MediaUrl falls back to the plain disk URL rather than throwing.
    |
    */
    'routes' => [
        'register' => true,
        'prefix' => 'admin',
        'middleware' => ['web', 'auth'],

        'names' => [
            'index' => 'admin.media',
            'edit' => 'admin.media.edit',
            'asset' => 'media.asset',
            'home' => '/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Enforced twice on purpose: as validation rules so the user gets a real
    | error message, and again inside UploadMediaAction so a caller that skips
    | validation cannot write an arbitrary file type.
    |
    */
    'uploads' => [
        'max_size_kb' => 10240,

        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt',
            'mp4', 'webm', 'mp3', 'wav',
        ],

        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/svg+xml',
            'application/pdf', 'text/plain', 'text/csv',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'video/mp4', 'video/webm', 'audio/mpeg', 'audio/wav',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image editing
    |--------------------------------------------------------------------------
    |
    | Bounds for the in-browser editor. Zoom in particular must be capped: the
    | payload arrives from the client, and an unbounded scale on a large source
    | is a one-request memory exhaustion.
    |
    */
    'editor' => [
        'max_zoom' => 4.0,
        'min_zoom' => 0.1,
        'max_dimension' => 6000,
    ],

    /*
    |--------------------------------------------------------------------------
    | WebP conversion
    |--------------------------------------------------------------------------
    */
    'webp' => [
        'enabled' => true,
        'queue' => env('MEDIA_WEBP_QUEUE', null),
        'quality' => 85,
        'max_width' => 2400,
    ],
];
