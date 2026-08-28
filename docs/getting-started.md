# Getting started

## Install

```bash
composer require kreetancraft/laravel-media-manager
```
```bash
php artisan vendor:publish --tag=media-config
```
```bash
php artisan migrate
```

Then point the admin screens at your own layout:

```php
// config/media.php
'layouts' => ['admin' => 'components.layouts.app'],
```

That's the whole setup. Visit `/admin/media`.

## What you get

| Route | Name | Auth |
|---|---|---|
| `/admin/media` | `admin.media` | yes |
| `/admin/media/{id}/edit` | `admin.media.edit` | yes |
| `/assets/{path}` | `media.asset` | **no** — public |

Four Livewire components, registered for you:

| Tag | Purpose |
|---|---|
| `<livewire:media.gallery />` | Full library browser with folders |
| `<livewire:media.picker />` | Drop into any form to choose a file |
| `<livewire:media.details />` | Metadata panel for one item |
| `<livewire:media.editor />` | Crop, rotate, flip, brightness, contrast |

## Prerequisites the package does not install for you

**A layout.** This package ships no CSS and no layouts. Its screens render into
yours and inherit your Tailwind + Flux theme. If `media.layouts.admin` points at
a view that doesn't exist, the gallery will error — that's the one setup step you
cannot skip.

**A permission (optional).** `MediaPolicy` checks whatever `media.permission`
names, default `manage-media`. If your user model can't answer `can()` — no
authorization package installed — every authenticated user is allowed, so the
library still works out of the box. Create the permission if you want it
enforced:

```php
Permission::findOrCreate('manage-media', 'web');
Role::findByName('editor')->givePermissionTo('manage-media');
```

**A root folder.** The gallery creates one on first visit. Nothing to do.

## The one thing that will bite you

`attachedMedia()` falls back to a query when the relation isn't loaded — and
because it calls the relation as a *method*, Laravel's `preventLazyLoading()`
cannot see it. That blind spot is exactly how a family of N+1 queries went
unnoticed in the application this package came from.

Always eager load:

```php
Article::with('mediaAttachments.media')->paginate();
```

Turn on the warning in development and the package will log the ones you miss:

```php
// config/media.php
'warn_on_lazy_attachments' => true,
```

## Next

- [Attaching media to your models](attaching-media.md)
- [Configuration reference](configuration.md)
- [Extending and replacing behaviour](extending.md)
