<?php

namespace Kreetancraft\Media\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Kreetancraft\Media\Models\MediaAttachment;
use Spatie\Permission\Models\Permission;

/**
 * Default authorization for the media library.
 *
 * This package does not manage roles or permissions. Its screens ask Laravel the
 * ordinary question — `$this->authorize('update', MediaAttachment::class)` — and
 * this policy answers it. No component anywhere in the package names a
 * permission string.
 *
 * That leaves two clean ways to control access:
 *
 *   1. Install kreetancraft/laravel-user-management. It discovers this policy
 *      through Gate::policies(), derives `view-media`, `create-media`,
 *      `update-media`, `delete-media` from PERMISSION_SUBJECT below, and creates
 *      them. Nothing is declared to it; nothing is configured here.
 *
 *   2. Replace this policy entirely — `Gate::policy(MediaAttachment::class,
 *      YourPolicy::class)` in your own provider — and answer however you like.
 *
 * Installed on its own with no permissions anywhere, the library is open: there
 * is nothing to enforce yet. It starts enforcing the moment permissions exist.
 *
 * The model argument is optional throughout because the screens authorize
 * against the class, not a loaded row — the library grants access to the
 * collection, not to individual files.
 */
class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * The noun this policy's permissions are about.
     *
     * Read by user-management when it discovers this policy, and used below to
     * build the ability names. One declaration, both sides — nothing to keep in
     * sync. Without it the subject would fall back to the model name and produce
     * `view-media-attachments`, which names a link table rather than anything an
     * administrator would recognise.
     */
    public const PERMISSION_SUBJECT = 'media';

    public function viewAny(Authenticatable $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function view(Authenticatable $user, ?MediaAttachment $attachment = null): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(Authenticatable $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(Authenticatable $user, ?MediaAttachment $attachment = null): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(Authenticatable $user, ?MediaAttachment $attachment = null): bool
    {
        return $this->allows($user, 'delete');
    }

    /**
     * The ability name for an action, e.g. `view-media`.
     */
    public function ability(string $action): string
    {
        return $action.'-'.Str::plural(Str::kebab(self::PERMISSION_SUBJECT));
    }

    private function allows(Authenticatable $user, string $action): bool
    {
        if (! method_exists($user, 'can')) {
            return true;
        }

        if ($user->can($this->ability($action))) {
            return true;
        }

        return ! $this->permissionsInUse();
    }

    /**
     * Whether this application uses permissions at all.
     *
     * Checked system-wide rather than per-ability: once ANY permission exists
     * the application is using them, and an ability nobody created must read as
     * denied, not as unconfigured.
     *
     * Deliberately forgiving — a missing package, a missing table or no database
     * yet all mean "not in use", so a fresh install boots open rather than
     * locking everyone out of the screen that would fix it.
     */
    private function permissionsInUse(): bool
    {
        if (! class_exists(Permission::class)) {
            return false;
        }

        try {
            return Permission::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
