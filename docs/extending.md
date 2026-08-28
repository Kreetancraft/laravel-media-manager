# Extending and replacing behaviour

## Contracts

Three interfaces are bound in the container. Rebind any of them in your own
service provider to replace that half of the package without forking it.

| Contract | Default | Responsibility |
|---|---|---|
| `MediaContract` | `MediaService` | attach, clear, URL building |
| `MediaItemsContract` | `MediaRepository` | queries — pagination, navigation, months |
| `FolderContract` | `FolderRepository` | folder CRUD, breadcrumbs, move targets |

```php
public function register(): void
{
    $this->app->bind(
        \Kreetancraft\Media\Contracts\MediaItemsContract::class,
        \App\Media\SearchIndexedMediaRepository::class,
    );
}
```

### `MediaContract`

```php
attach(HasMedia $owner, UploadedFile|string $file, string $collection, ?string $name = null, string $conversion = ''): string
clear(HasMedia $owner, string $collection): void
urlFor(HasMedia $owner, string $collection, string $conversion = ''): ?string
webpUrlFor(Media $media): ?string
publicUrl(Media $media, ?string $conversion = null): string
```

### `MediaItemsContract`

```php
find(int $id): ?Media
findWhereIn(array $ids): Collection
delete(Media $media): void
update(Media $media, array $attributes): Media
paginateInFolder(?Folder $folder, ...): LengthAwarePaginator
navigationIdsInFolder(?Folder $folder): array
monthsWithUploads(): Collection
subfoldersIn(?Folder $folder): Collection
```

### `FolderContract`

```php
create(array $attributes): Folder
find(int $id): ?Folder
findOrFail(int $id): Folder
delete(Folder $folder): void
update(Folder $folder, array $attributes): Folder
breadcrumbTo(?Folder $folder): Collection
moveTargetsExcluding(array $folderIds): Collection
folderIdsInParent(?Folder $parent): array
slugExistsInParent(string $slug, ?int $parentId, ?int $ignoreId = null): bool
```

## The user model

This package never imports a concrete user class. Uploaders resolve through
`config('auth.providers.users.model')` via `Kreetancraft\Media\Support\UserResolver`,
and `MediaPolicy` type-hints `Authenticatable`.

Nothing to configure — if your auth config is correct, this works.

## Wiring avatars into `kreetancraft/laravel-user-management`

That package ships no image handling and exposes `User::avatarUrl()` returning
`null` as the extension point. To back it with this one:

```php
use Kreetancraft\Media\Concerns\HasMediaAttachments;

class User extends \Kreetancraft\UserManagement\Models\User
{
    use HasMediaAttachments;

    public function avatarUrl(): ?string
    {
        return $this->attachedUrl('avatar', 'thumb');
    }
}
```

Then point `config('auth.providers.users.model')` at your subclass. The user
package's tables and views pick it up with no further changes — that is the
whole reason the hook exists.

## Replacing a screen

Every route name is configurable, so you can point `media.routes.names.index` at
your own controller and keep the picker, the trait and the URL helpers.

To keep the screens but restyle them:

```bash
php artisan vendor:publish --tag=media-views
```

Published views live in `resources/views/vendor/media/` and **override the
package**. They will not pick up upstream changes — republish with `--force`
after an upgrade, or you will keep rendering the old markup while wondering why.

## Events

The package listens for Spatie's `MediaHasBeenAddedEvent` to queue WebP
conversion. It emits none of its own yet; if you need hooks for an audit trail,
open an issue rather than reaching into the actions.
