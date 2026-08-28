<?php

use Kreetancraft\Media\Tests\Fixtures\Models\User;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('guests are redirected to login', function () {
    $this->get(route('admin.media'))->assertRedirect(route('login'));
});

test('an authenticated user without the permission is forbidden', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.media'))->assertForbidden();
});

test('a role holding the permission can view the media page', function () {
    actingAsSuperAdmin();

    $this->get(route('admin.media'))->assertOk()->assertSee('Media');
});

test('a role without the permission cannot view the media page', function () {
    actingAsRole('no-access');

    $this->get(route('admin.media'))->assertForbidden();
});

test('access follows the configured ability rather than a fixed name', function () {
    config()->set('media.permission', 'library-admin');

    // Holding only the old ability is no longer enough.
    actingAsSuperAdmin();

    $this->get(route('admin.media'))->assertForbidden();
});
