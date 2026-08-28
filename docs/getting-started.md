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

**Permissions (optional).** This package names no permission. Its screens ask
the ordinary authorization question — `viewAny`, `create`, `update`, `delete` on
`MediaAttachment` — and `MediaPolicy` answers it. Until any permission exists
anywhere in your app, the policy treats the library as open, so it works on a
bare install; it starts enforcing the moment you create permissions.

The abilities are `view-media`, `create-media`, `update-media`, `delete-media`.
Create them by hand, or install
[kreetancraft/laravel-user-management](https://github.com/Kreetancraft/laravel-user-management)
and run one command — it finds this policy on its own, with nothing declared on
either side:

```bash
php artisan user-management:sync-permissions
```

```php
Role::findByName('editor')->givePermissionTo('view-media', 'create-media');
```

**A sidebar link (automatic).** The package contributes one to the
`admin.navigation` container tag. If something collects that tag — the user
management package's sidebar does — a **Media** link appears with the right
icon, ordering and visibility. Nothing to wire; if nothing collects it, the
binding is never resolved and costs nothing.

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
