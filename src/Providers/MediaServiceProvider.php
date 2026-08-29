<?php

namespace Kreetancraft\Media\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Kreetancraft\Media\Console\Commands\ReconvertWebp;
use Kreetancraft\Media\Contracts\FolderContract;
use Kreetancraft\Media\Contracts\MediaContract;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Kreetancraft\Media\Listeners\DispatchWebpConversion;
use Kreetancraft\Media\Livewire\AvatarUploader;
use Kreetancraft\Media\Livewire\MediaDetails;
use Kreetancraft\Media\Livewire\MediaEditor;
use Kreetancraft\Media\Livewire\MediaGallery;
use Kreetancraft\Media\Livewire\MediaPicker;
use Kreetancraft\Media\Models\MediaAttachment;
use Kreetancraft\Media\Policies\MediaPolicy;
use Kreetancraft\Media\Repositories\FolderRepository;
use Kreetancraft\Media\Repositories\MediaRepository;
use Kreetancraft\Media\Services\MediaService;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/media.php', 'media');

        $this->app->bind(MediaContract::class, MediaService::class);
        $this->app->singleton(FolderContract::class, FolderRepository::class);
        $this->app->singleton(MediaItemsContract::class, MediaRepository::class);

        $this->registerNavigation();
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerMigrations();
        $this->registerRoutes();

        Gate::policy(MediaAttachment::class, MediaPolicy::class);

        Event::listen(MediaHasBeenAddedEvent::class, DispatchWebpConversion::class);

        Livewire::component('media.picker', MediaPicker::class);
        Livewire::component('media.avatar-uploader', AvatarUploader::class);
        Livewire::component('media.gallery', MediaGallery::class);
        Livewire::component('media.details', MediaDetails::class);
        Livewire::component('media.editor', MediaEditor::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ReconvertWebp::class]);
        }
    }

    /**
     * This package's sidebar link, contributed through a container tag.
     *
     * The same shape user-management uses for its own links — the one
     * difference is that the tag is written out rather than referenced as
     * Navigation::TAG, because naming that class would be a dependency on it.
     *
     * Tags are collected at render time, so provider order does not matter, and
     * a binding nobody collects is never resolved. Install this package alone
     * and nothing reads the tag; install it beside user-management and the link
     * appears in the sidebar with nothing declared either way.
     *
     * The check is the same policy question routes/web.php asks, so the link
     * appears exactly when the page behind it is reachable — a permission name
     * would hide it on an install that has no permissions at all.
     */
    protected function registerNavigation(): void
    {
        $this->app->bind('media.navigation.items', fn () => [
            [
                'label' => __('Media'),
                'icon' => 'photo',
                'route' => config('media.routes.names.index', 'admin.media'),
                'ability' => 'viewAny',
                'model' => MediaAttachment::class,
                'sort' => 30,
            ],
        ]);

        $this->app->tag('media.navigation.items', 'admin.navigation');
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__.'/../../config/media.php' => config_path('media.php'),
        ], 'media-config');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'media');

        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/media'),
        ], 'media-views');

        Blade::anonymousComponentPath(__DIR__.'/../../resources/views/components', 'media');
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->publishesMigrations([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'media-migrations');
    }

    /**
     * Two independent route groups, each opt-out.
     *
     * The admin screens and the public asset route are separate switches on
     * purpose: a host may want its public images served while replacing the
     * admin UI entirely, or vice versa.
     */
    protected function registerRoutes(): void
    {
        if (config('media.routes.register', true)) {
            Route::group([
                'prefix' => config('media.routes.prefix', 'admin'),
                'middleware' => config('media.routes.middleware', ['web', 'auth']),
            ], function (): void {
                $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
            });
        }

        // Public and unauthenticated: these URLs appear on public pages.
        if (config('media.routes.serve_assets', true)) {
            Route::middleware(config('media.routes.asset_middleware', ['web']))
                ->group(function (): void {
                    $this->loadRoutesFrom(__DIR__.'/../../routes/assets.php');
                });
        }
    }
}
