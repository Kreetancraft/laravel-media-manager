<?php

namespace Kreetancraft\Media\Tests;

use Flux\FluxServiceProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Kreetancraft\Media\Providers\MediaServiceProvider;
use Kreetancraft\Media\Tests\Fixtures\Models\User;
use Livewire\LivewireServiceProvider;
use LivewireFilemanager\Filemanager\FilemanagerServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FluxServiceProvider::class,
            MediaLibraryServiceProvider::class,
            FilemanagerServiceProvider::class,
            PermissionServiceProvider::class,
            MediaServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            // SQLite ignores foreign keys unless asked. Without this the
            // cascade deletes this package relies on are never exercised.
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('filesystems.default', 'public');

        // This package resolves the user model from auth config and never
        // imports a concrete class, so the suite points it at the fixture.
        $app['config']->set('auth.providers.users.model', User::class);

        // tests/fixtures/views stands in for the host: this package ships no
        // layouts, so the suite provides the one its screens render into.
        $app['config']->set('view.paths', [
            __DIR__.'/fixtures/views',
            __DIR__.'/../resources/views',
            resource_path('views'),
        ]);
    }

    /**
     * Host-owned routes this package references by configurable name.
     */
    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->get('/', fn () => 'home')->name('home');
            $router->get('/login', fn () => 'login')->name('login');
            $router->get('/assets/{path}', fn () => 'asset')->where('path', '.*')->name('media.asset');
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        // Host-owned tables first (users), then spatie/permission's, then ours.
        $this->loadMigrationsFrom(__DIR__.'/fixtures/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
