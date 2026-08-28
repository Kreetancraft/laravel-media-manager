<?php

namespace Kreetancraft\Media\Support;

use Illuminate\Database\Eloquent\Model;
use Kreetancraft\Media\Concerns\HasMediaAttachments;

/**
 * Supplies avatars to kreetancraft/laravel-user-management.
 *
 * That package ships no image handling: `User::avatarUrl()` returns null unless
 * a resolver is named in its config. Point it here and user avatars come from
 * the media library:
 *
 *     // config/user-management.php
 *     'avatar_resolver' => \Kreetancraft\Media\Support\MediaAvatarResolver::class,
 *
 * Nothing else changes, and neither package declares a dependency on the other.
 * There is no framework registry for "who provides avatars" the way
 * Gate::policies() serves permissions, so this seam has to be named explicitly.
 */
class MediaAvatarResolver
{
    public function __invoke(Model $user): ?string
    {
        return $this->avatarFor($user);
    }

    public function avatarFor(Model $user): ?string
    {
        // The host's user model has to opt in by using the trait; without it
        // there is no attachment relation to read.
        if (! in_array(HasMediaAttachments::class, class_uses_recursive($user), true)) {
            return null;
        }

        $collection = (string) config('media.avatar.collection', 'avatar');
        $conversion = config('media.avatar.conversion');

        return $user->attachedUrl($collection, $conversion);
    }
}
