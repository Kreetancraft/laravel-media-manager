<?php

namespace Kreetancraft\Media\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Kreetancraft\Media\Models\MediaAttachment;

/**
 * Authorization for the media library.
 *
 * Type-hinted on Authenticatable rather than a concrete User: this package does
 * not know, and must not care, which user model the host uses. The required
 * ability is configurable for the same reason.
 */
class MediaPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function view(Authenticatable $user, MediaAttachment $mediaAttachment): bool
    {
        return $this->allows($user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function update(Authenticatable $user, MediaAttachment $mediaAttachment): bool
    {
        return $this->allows($user);
    }

    public function delete(Authenticatable $user, MediaAttachment $mediaAttachment): bool
    {
        return $this->allows($user);
    }

    private function allows(Authenticatable $user): bool
    {
        $ability = (string) config('media.permission', 'manage-media');

        // A host with no authorization package still gets a working library: if
        // the model cannot answer can(), any authenticated user is allowed.
        if (! method_exists($user, 'can')) {
            return true;
        }

        return $user->can($ability);
    }
}
