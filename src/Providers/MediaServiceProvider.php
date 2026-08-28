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
        Livewire::component('media.gallery', MediaGallery::class);
        Livewire::component('media.details', MediaDetails::class);
        Livewire::component('media.editor', MediaEditor::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ReconvertWebp::class]);
        }
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
     * Routes are opt-out and fully configurable — prefix, middleware and every
     * route name. A host that only wants the picker component can turn them off.
     */
    protected function registerRoutes(): void
    {
        if (! config('media.routes.register', true)) {
            return;
        }

        Route::group([
            'prefix' => config('media.routes.prefix', 'admin'),
            'middleware' => config('media.routes.middleware', ['web', 'auth']),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        });
    }
}
