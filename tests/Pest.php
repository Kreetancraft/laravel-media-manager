<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Policies\MediaPolicy;
use Kreetancraft\Media\Tests\Fixtures\Models\User;
use Kreetancraft\Media\Tests\TestCase;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature', 'Unit');

/**
 * The single permission this package checks, plus a role that holds it.
 *
 * The package seeds nothing and names no roles of its own — `media.permission`
 * is configurable — so the suite declares what it needs.
 */
function seedRolesAndPermissions(): void
{
    // Ask the policy which abilities it enforces rather than hardcoding names
    // the tests would then have to keep in step with it.
    $policy = new MediaPolicy;
    $abilities = array_map(
        fn (string $action) => $policy->ability($action),
        ['view', 'create', 'update', 'delete'],
    );

    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    Role::findOrCreate('media-manager', 'web')->syncPermissions($abilities);
    Role::findOrCreate('no-access', 'web');

    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

/**
 * Act as a user in the given role. Unknown roles are created with no
 * permissions, which is what you want for "this person may not".
 */
function actingAsRole(string $role): User
{
    seedRolesAndPermissions();

    Role::findOrCreate($role, 'web');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

/**
 * Act as someone who can manage media.
 *
 * Named for continuity with the tests inherited from the host application; in
 * this package it simply means "holds the media permission".
 */
function actingAsSuperAdmin(): User
{
    return actingAsRole('media-manager');
}

/**
 * The root folder, created on demand.
 */
function home(): Folder
{
    return Folder::whereNull('parent_id')->first()
        ?? Folder::create(['name' => 'Home', 'slug' => 'home', 'parent_id' => null]);
}

/**
 * Persist a fake file and attach it to a folder's media collection.
 */
function storeFile(Folder $folder, string $name, string $mime = 'image/jpeg'): Media
{
    $upload = $mime === 'application/pdf'
        ? UploadedFile::fake()->create($name, 100, $mime)
        : UploadedFile::fake()->image($name);

    $stored = Storage::disk('public')->putFileAs('tmp', $upload, $name);

    return $folder->addMedia(Storage::disk('public')->path($stored))->toMediaCollection('medialibrary');
}
