<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('the configured permission exists after seeding', function () {
    expect(
        Permission::where('name', config('media.permission'))->where('guard_name', 'web')->exists()
    )->toBeTrue();
});

test('a role granted the permission holds it', function () {
    expect(Role::findByName('media-manager', 'web')->hasPermissionTo('manage-media'))->toBeTrue();
});

test('a role without the permission does not hold it', function () {
    expect(Role::findByName('no-access', 'web')->hasPermissionTo('manage-media'))->toBeFalse();
});

test('the checked ability follows config', function () {
    // The package names no permission of its own — the host decides which
    // ability MediaPolicy checks.
    config()->set('media.permission', 'library-admin');

    Permission::findOrCreate('library-admin', 'web');
    $role = Role::findOrCreate('librarian', 'web');
    $role->syncPermissions(['library-admin']);

    expect($role->hasPermissionTo(config('media.permission')))->toBeTrue();
});
