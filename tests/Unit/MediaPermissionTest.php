<?php

use Kreetancraft\Media\Policies\MediaPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('the configured permission exists after seeding', function () {
    expect(
        Permission::where('name', (new MediaPolicy)->ability('view'))->where('guard_name', 'web')->exists()
    )->toBeTrue();
});

test('a role granted the permission holds it', function () {
    expect(Role::findByName('media-manager', 'web')->hasPermissionTo('view-media'))->toBeTrue();
});

test('a role without the permission does not hold it', function () {
    expect(Role::findByName('no-access', 'web')->hasPermissionTo('view-media'))->toBeFalse();
});
