# kreetancraft/laravel-media-manager

Media manager for Laravel — folders, a picker you drop into any form, an
in-browser image editor, and automatic WebP conversion. **Livewire 4 + Flux UI**,
built on `spatie/laravel-medialibrary`.

Standalone package. No `nwidart/laravel-modules`, no bundled CSS, no bundled
layouts, no dependency on any particular user package.

```bash
composer require kreetancraft/laravel-media-manager
php artisan vendor:publish --tag=media-config
php artisan migrate
```

Point it at your layout and you're done:

```php
// config/media.php
'layouts' => ['admin' => 'components.layouts.app'],
```

## Documentation

| | |
|---|---|
| [Getting started](docs/getting-started.md) | Install, routes, components, the one gotcha |
| [Attaching media to your models](docs/attaching-media.md) | The trait, collections, the picker, uploads, URLs |
| [Configuration reference](docs/configuration.md) | Every key, and why it exists |
| [Extending and replacing behaviour](docs/extending.md) | Contracts, the avatar hook, publishing views |

## Features

**Library** — folder hierarchy with breadcrumbs, drag-free move between folders,
bulk select and delete, filter by upload month, search.

**Picker** — `<livewire:media.picker wire:model="imageId" />` in any form. Browse,
upload, pick, done.

**Editor** — crop, rotate, flip, brightness, contrast. Saves a WebP variant over
the auto conversion so serving keeps working.

**Attachments** — a many-to-many link, unlike Spatie's 1:1 ownership. One image
can belong to many models; deleting a model drops its attachments and keeps the
shared file.

```php
$article->attachMedia($id, 'gallery');
$article->attachedMedia('gallery');   // Collection<Media>, sorted
$article->featuredUrl('webp');        // ?string
$article->syncAttachedMedia([$a, $b], 'gallery');
```

**WebP** — every upload gets a variant, queued. `media:reconvert-webp` backfills
an existing library.

**Public serving** — `/assets/{path}` serves files by exact folder path,
WordPress-style, with no enumerable listing surface.

## Let Tailwind see this package

Required. Tailwind v4 generates only the classes it finds by scanning files, and
it does not scan `vendor/`. In `resources/css/app.css`:

```css
@source '../../vendor/kreetancraft/laravel-media-manager/resources/views';
```

Skipping it fails confusingly rather than loudly — classes shared with your own
views still work and only the ones unique to this package go missing, which
typically shows up as a light filter bar on a dark page.

## Supplying images to other packages

`kreetancraft/laravel-seo` and `kreetancraft/laravel-blog` ship no image handling on purpose: a
PHP class cannot conditionally `use` a trait, so pulling this package's trait into their models
would make it a hard dependency and a missing one a fatal error. They ask a configured resolver
instead — point it here and images appear:

```php
// config/blog.php  and  config/seo.php
'image_resolver' => \Kreetancraft\Media\Support\MediaImageResolver::class,
```

It also ships the field itself, so nobody has to write a picker before they can attach an image:

```php
// config/blog.php
'media_picker_view' => 'media::picker-field',

// config/seo.php
'og_picker_view' => 'media::picker-field',
```

That renders the chosen tiles, a **Choose** button and the picker modal, scoped to the `group` it
is given so two fields on one form cannot collide. It dispatches `media-picked` with ids, group
and items — the event those packages already listen for.

Neither package declares a dependency on this one, and this one declares none on them. The
resolver reads `media_attachments` polymorphically, so **the calling model does not need
`HasMediaAttachments`** — that is what lets another package's models resolve images without
inheriting anything from here.

It also exposes `preload()`, which those packages call once per page. Resolving per model is an
N+1 by construction, and preloading is what buys back what a real relation would have given.

### Letting people set their own picture

The picker browses the whole library and is gated on `viewAny` for media. That is right for an
admin attaching a featured image and wrong for a profile page: someone changing their own picture
should not be shown everyone else's files, nor need a permission over the library to do it.

`media.avatar-uploader` asks for a file and nothing else:

```blade
<livewire:media.avatar-uploader :model="auth()->user()" />
```

Authorization follows the **subject**, not the library — you may always set your own, and setting
someone else's is `update` on them. No media permission is involved either way.

kreetancraft/laravel-user-management uses it automatically when you name it:

```php
// config/user-management.php
'avatar_uploader' => 'media.avatar-uploader',
```

Its profile component then uploads, while the admin user forms keep the library chooser.

## Design decisions worth knowing

**It ships no CSS and no layouts.** Screens render into *your* layout and inherit
your Tailwind + Flux theme.

**It does not care which user model you have.** Uploaders resolve through
`config('auth.providers.users.model')`; `MediaPolicy` type-hints
`Authenticatable`.

**It names no permission of its own.** The screens ask the ordinary
authorization question and `MediaPolicy` answers it. Until permissions exist
anywhere in the app the library is open — it works on a bare Laravel install
rather than failing closed on a dependency you never asked for.

**It adds its own sidebar link, without knowing what renders the sidebar.** The
link is bound and tagged `admin.navigation`; anything collecting that tag picks
it up. Install it beside
[laravel-user-management](https://github.com/Kreetancraft/laravel-user-management)
and a **Media** entry appears in the admin sidebar with nothing declared either
way. Install it alone and the tag is simply never read.

**Routes are two independent switches.** Serve public assets while replacing the
admin UI, or the reverse.

## Requirements

- PHP `^8.2`, Laravel `^12|^13`
- `livewire/livewire ^4`, `livewire/flux ^2`
- `spatie/laravel-medialibrary`, `spatie/image`, `spatie/laravel-query-builder`
- `livewire-filemanager/filemanager` — supplies the `Folder` model the hierarchy
  is built on

## Testing

```bash
vendor/bin/pest
```

87 tests against `orchestra/testbench` on in-memory SQLite. `tests/fixtures`
supplies the host application's user model, layout and routes, since this
package deliberately ships none of them.

## License

MIT
