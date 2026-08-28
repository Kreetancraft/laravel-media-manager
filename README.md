# kreetancraft/laravel-media

Media library for Laravel — folders, a picker you can drop into any form, an
in-browser image editor, and automatic WebP conversion. **Livewire 4 + Flux UI**,
built on `spatie/laravel-medialibrary`.

Standalone package. No `nwidart/laravel-modules`, no bundled CSS, no bundled
layouts.

## Design decisions worth knowing before you install

**It ships no CSS and no layouts.** The gallery and editor render into *your*
layout and inherit your Tailwind + Flux theme. Set `media.layouts.admin` to
whatever your app uses.

**It does not care which user model you have.** Uploaders are resolved through
`config('auth.providers.users.model')`, and `MediaPolicy` type-hints
`Authenticatable`. There is no dependency on any user package.

**It names no permission of its own.** `MediaPolicy` checks whatever
`media.permission` says (default `manage-media`). If your user model cannot
answer `can()` — no authorization package installed — every authenticated user is
allowed, so the library still works out of the box.

**Routes are opt-out.** Set `media.routes.register` to `false` to keep the
Livewire components (the picker in particular) without mounting any routes.

## Requirements

- PHP `^8.2`, Laravel `^12|^13`
- `livewire/livewire ^4`, `livewire/flux ^2`
- `spatie/laravel-medialibrary`, `spatie/image`, `spatie/laravel-query-builder`
- `livewire-filemanager/filemanager` — supplies the `Folder` model this package
  builds its hierarchy on

## Installation

```bash
composer require kreetancraft/laravel-media
```
```bash
php artisan vendor:publish --tag=media-config
```
```bash
php artisan migrate
```

Then point the layout at your own:

```php
// config/media.php
'layouts' => ['admin' => 'components.layouts.app'],
```

## Usage

The gallery lives at `/admin/media` by default. To embed the picker in a form:

```blade
<livewire:media.picker wire:model="imageId" collection="featured" />
```

Attach media to any model with the trait:

```php
use Kreetancraft\Media\Concerns\HasMediaAttachments;

class Article extends Model
{
    use HasMediaAttachments;
}
```
```php
$article->attachMedia($mediaId, 'gallery');
$article->attachedMedia('gallery');   // Collection<Media>
$article->featuredUrl();
```

**Eager load it.** `attachedMedia()` falls back to a query when the relation is
not loaded, and because it calls the relation as a *method*, Laravel's
`preventLazyLoading()` cannot see it — which is exactly how a family of N+1s once
went unnoticed. Always:

```php
Article::with('mediaAttachments.media')->get();
```

Set `media.warn_on_lazy_attachments` to `true` in development and the package
will log the ones you miss.

## Configuration

`config/media.php`:

```php
'permission' => 'manage-media',

'layouts' => ['admin' => 'components.layouts.app'],

'routes' => [
    'register'   => true,
    'prefix'     => 'admin',
    'middleware' => ['web', 'auth'],
    'names'      => ['index' => 'admin.media', 'edit' => 'admin.media.edit',
                     'asset' => 'media.asset',  'home' => '/'],
],

// Enforced twice on purpose: as validation rules so the user gets a real error,
// and again inside UploadMediaAction so a caller that skips validation cannot
// write an arbitrary file type.
'uploads' => [
    'max_size_kb'        => 10240,
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', /* … */],
    'allowed_mimes'      => ['image/jpeg', 'image/png', /* … */],
],

// The editor payload arrives from the browser. Zoom in particular must be
// capped: an unbounded scale on a large source is a one-request memory
// exhaustion.
'editor' => ['max_zoom' => 4.0, 'min_zoom' => 0.1, 'max_dimension' => 6000],

'webp' => ['enabled' => true, 'queue' => null, 'quality' => 85, 'max_width' => 2400],
```

## Commands

```bash
php artisan media:reconvert-webp
```
Queues WebP conversion for library items missing a variant. Streams with
`cursor()` rather than loading the table.

## Testing

```bash
vendor/bin/pest
```

Runs against `orchestra/testbench` on in-memory SQLite. `tests/fixtures` supplies
the host application's user model, layout and routes, since this package ships
none of them.

## License

MIT
