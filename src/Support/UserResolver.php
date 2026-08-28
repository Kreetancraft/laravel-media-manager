<?php

namespace Kreetancraft\Media\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

/**
 * Resolves the host application's user model.
 *
 * This package records who uploaded a file, but it must not care which user
 * package the host uses — so it goes through the auth config rather than
 * importing a concrete class.
 */
final class UserResolver
{
    /**
     * @return class-string<Model>
     */
    public static function model(): string
    {
        /** @var class-string<Model> $model */
        $model = config('auth.providers.users.model', User::class);

        return $model;
    }

    public static function find(int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        return self::model()::find($id);
    }

    /**
     * Uploaders keyed by id, for resolving a page of files in one query.
     *
     * @param  iterable<int|string>  $ids
     * @return Collection<int|string, Model>
     */
    public static function keyedById(iterable $ids): Collection
    {
        $ids = collect($ids)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return self::model()::query()->whereKey($ids)->get()->keyBy(
            (new (self::model()))->getKeyName()
        );
    }

    /**
     * Display name for an uploader, falling back when the user is gone.
     */
    public static function nameFor(?Model $user): string
    {
        return $user?->getAttribute('name') ?? __('Unknown');
    }
}
