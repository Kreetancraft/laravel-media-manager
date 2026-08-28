<?php

use Kreetancraft\Media\Models\MediaAttachment;
use Kreetancraft\Media\Policies\MediaPolicy;
use Kreetancraft\Media\Tests\Fixtures\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The granular abilities, and the fallbacks that keep this package usable alone.
 *
 * Nothing here declares a permission name to the user-management package. Both
 * derive the same strings from MediaAttachment, so they agree by construction —
 * these tests pin that derivation so a rename on either side is caught.
 */
function grantAbilities(array $abilities): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('scoped', 'web');
    $role->syncPermissions($abilities);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->assignRole('scoped');

    return $user;
}

test('abilities are derived from the model, not declared', function () {
    $policy = new MediaPolicy;

    expect($policy->ability('view'))->toBe('view-media')
        ->and($policy->ability('delete'))->toBe('delete-media');
});

test('view access does not grant delete', function () {
    $user = grantAbilities(['view-media']);
    $policy = new MediaPolicy;
    $attachment = new MediaAttachment;

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->delete($user, $attachment))->toBeFalse();
});

test('delete access is granted by its own ability', function () {
    $user = grantAbilities(['delete-media']);

    expect((new MediaPolicy)->delete($user, new MediaAttachment))->toBeTrue();
});

test('holding neither ability refuses', function () {
    $user = grantAbilities(['something-unrelated']);

    expect((new MediaPolicy)->viewAny($user))->toBeFalse();
});

test('renaming the subject changes the ability checked', function () {
    // Must be kept in step with policies.subjects on the user-management side.
    config()->set('media.permission_subject', 'media');

    expect((new MediaPolicy)->ability('view'))->toBe('view-media');
});

test('a fresh install with no permissions defined allows access', function () {
    // Every Laravel user model has can() via Authorizable, so "can it answer"
    // is not the question — "has anyone actually created this permission" is.
    // Enforcing one nobody created would lock everyone out of a standalone
    // install on first boot.
    Permission::query()->delete();

    $user = User::factory()->create();

    expect((new MediaPolicy)->viewAny($user))->toBeTrue();
});

test('once a permission exists it is enforced', function () {
    // Defining it flips the library from open to closed — no config change.
    Permission::findOrCreate('view-media', 'web');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();

    expect((new MediaPolicy)->viewAny($user))->toBeFalse();
});
