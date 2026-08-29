<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | There is nothing to configure here, on purpose.
    |
    | MediaPolicy declares its own subject (MediaPolicy::PERMISSION_SUBJECT) and
    | builds its abilities from it: view-media, create-media, update-media,
    | delete-media. kreetancraft/laravel-user-management reads that same constant
    | when it discovers the policy, so both sides agree without a pair of config
    | keys anyone has to keep in step.
    |
    | Create them with:  php artisan user-management:sync-permissions
    |
    | Installed on its own, with no permissions anywhere in the application, the
    | library is open — there is nothing to enforce yet. It starts enforcing the
    | moment permissions are in use.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Avatars
    |--------------------------------------------------------------------------
    |
    | Used only when kreetancraft/laravel-user-management points its
    | `avatar_resolver` at MediaAvatarResolver. The host user model must also
    | use the HasMediaAttachments trait — without it there is no attachment
    | relation to read, and the resolver returns null rather than throwing.
    |
    */
    'avatar' => [
        'collection' => 'avatar',
        'conversion' => null,

        // Folder an uploaded avatar is stored in, by name. Null, or a name that
        // does not exist, stores it at the library root.
        'folder' => null,
    ],

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
        // Admin screens (gallery, editor).
        'register' => true,
        'prefix' => 'admin',
        'middleware' => ['web', 'auth'],

        // Public asset serving. Independent of the admin screens: these URLs
        // appear on public pages, so they carry no auth.
        'serve_assets' => true,
        'asset_prefix' => 'assets',
        'asset_middleware' => ['web'],

        // Where the "Dashboard" breadcrumb points. A route name or a URL —
        // a route name is better, since it survives the route moving.
        'home' => 'dashboard',

        'names' => [
            'index' => 'admin.media',
            'edit' => 'admin.media.edit',
            'asset' => 'media.asset',
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
