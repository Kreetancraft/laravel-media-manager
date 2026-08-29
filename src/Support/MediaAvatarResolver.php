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
 *
 * Reading was all this did until 0.8.0, which made the seam useless in practice:
 * an avatar could be displayed but nothing could set one, so the user forms had
 * no image option and no way to gain one. listFor() and syncFor() are the other
 * half, and they are what the avatar field on those forms saves through.
 */
class MediaAvatarResolver
{
    public function __construct(private readonly MediaImageResolver $images = new MediaImageResolver) {}

    public function __invoke(Model $user): ?string
    {
        return $this->avatarFor($user);
    }

    public function avatarFor(Model $user): ?string
    {
        // With the trait, go through it: it is the only path that honours
        // `media.avatar.conversion`, and a host using it expects that.
        if (in_array(HasMediaAttachments::class, class_uses_recursive($user), true)) {
            return $user->attachedUrl($this->collection(), config('media.avatar.conversion'));
        }

        // Without it, read the attachments directly. Requiring the trait meant
        // an application whose User model this package does not own could never
        // have an avatar at all.
        return $this->images->urlFor($user, $this->collection());
    }

    /**
     * The avatar, shaped for a picker. Zero or one entry — an avatar is single.
     *
     * @return list<array{id: int, url: ?string, name: ?string}>
     */
    public function listFor(Model $user, ?string $collection = null): array
    {
        return $this->images->listFor($user, $collection ?? $this->collection());
    }

    /**
     * Set or clear the avatar. An empty list removes it.
     *
     * @param  list<int|string>  $ids
     */
    public function syncFor(Model $user, string $collection, array $ids): void
    {
        // Only ever one avatar, whatever the picker sends.
        $this->images->syncFor($user, $collection ?: $this->collection(), array_slice($ids, 0, 1));
    }

    private function collection(): string
    {
        return (string) config('media.avatar.collection', 'avatar');
    }
}
