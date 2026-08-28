<?php

namespace Kreetancraft\Media\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Kreetancraft\Media\Tests\Fixtures\Database\Factories\UserFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * Stand-in for the HOST application's user model.
 *
 * This package resolves the user model from auth config and never imports a
 * concrete class, so the suite has to supply one. Test scaffolding only.
 */
class User extends Authenticatable implements HasMedia
{
    use HasFactory, HasRoles, InteractsWithMedia;

    protected $table = 'users';

    protected $guarded = [];

    protected string $guard_name = 'web';

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
