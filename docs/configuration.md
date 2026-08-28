# Configuration reference

Everything below lives in `config/media.php` after
`php artisan vendor:publish --tag=media-config`.

## `permission`

```php
'permission' => 'manage-media',
```

The ability `MediaPolicy` checks. The package names no permission of its own —
this is the only place it appears.

If your user model cannot answer `can()` — no authorization package installed —
every authenticated user is allowed. That is deliberate: the library should work
on a bare Laravel install, not fail closed on a dependency you never asked for.

## `layouts`

```php
'layouts' => ['admin' => 'components.layouts.app'],
```

A **view** name, passed to Livewire's `->layout()`. This package ships no
layouts; its screens render into yours. The default matches a stock Laravel
starter kit.

## `routes`

Two independent switches. A host may want its public images served while
replacing the admin UI entirely, or vice versa.

```php
'routes' => [
    // Admin screens (gallery, editor)
    'register'   => true,
    'prefix'     => 'admin',
    'middleware' => ['web', 'auth'],

    // Public asset serving — no auth, these URLs appear on public pages
    'serve_assets'     => true,
    'asset_prefix'     => 'assets',
    'asset_middleware' => ['web'],

    'names' => [
        'index' => 'admin.media',
        'edit'  => 'admin.media.edit',
        'asset' => 'media.asset',
        'home'  => '/',          // breadcrumb target; a URL, not a route name
    ],
],
```

Point `names.*` at your own routes to replace any screen without forking views.

**Why the asset route is public:** it serves images embedded in public pages.
`FileController` 404s on anything but an exact folder-path + filename match, so
there is no enumerable listing surface.

## `uploads`

```php
'uploads' => [
    'max_size_kb'        => 10240,
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg',
                             'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt',
                             'mp4', 'webm', 'mp3', 'wav'],
    'allowed_mimes'      => ['image/jpeg', 'image/png', /* … */],
],
```

Extension and MIME are both checked. Narrow these before going near
user-generated uploads.

## `editor`

```php
'editor' => [
    'max_zoom'      => 4.0,
    'min_zoom'      => 0.1,
    'max_dimension' => 6000,
],
```

The editor payload arrives from the browser and is therefore untrusted. Zoom in
particular must be capped: an unbounded scale on a large source is a
one-request memory exhaustion. `EditMediaAction` clamps every transform and
refuses a result larger than `max_dimension` on either axis.

## `webp`

```php
'webp' => [
    'enabled'   => true,
    'queue'     => env('MEDIA_WEBP_QUEUE', null),
    'quality'   => 85,
    'max_width' => 2400,
],
```

## `warn_on_lazy_attachments`

```php
'warn_on_lazy_attachments' => false,
```

Turn on in development. Logs a warning whenever `attachedMedia()` had to query
because the caller forgot `->with('mediaAttachments.media')`. Silent in
production and in console commands — a log line per row is worse than the N+1.

## Database tables

| Table | Owned by | Notes |
|---|---|---|
| `media` | this package | Spatie medialibrary's schema |
| `folders` | this package | hierarchy, from `livewire-filemanager` |
| `media_attachments` | this package | the many-to-many link |

`media.collection_name` is indexed — the gallery, the picker and
`media:reconvert-webp` all filter on it, and it was a full table scan before.

**If your app already published Spatie's `create_media_table`**, you will have
two migrations creating the same table. Delete one; keep whichever has already
run.
