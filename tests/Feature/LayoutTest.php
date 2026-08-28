<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Kreetancraft\Media\Layout;

/**
 * Mirrors user-management's LayoutTest on purpose. Two packages that behave
 * differently about the host's layout are two things to remember instead of one.
 */
test('a missing configured layout falls back to a convention', function () {
    config()->set('media.layouts.admin', 'does.not.exist');

    expect(Layout::CONVENTIONS)->toContain('components.layouts.app')
        ->and(Layout::CONVENTIONS)->toContain('layouts.app')
        ->and(Layout::admin())->toBeIn(Layout::CONVENTIONS);
});

test('it fails with the config key and what it tried, not just a view name', function () {
    config()->set('media.layouts.admin', 'nope');

    View::getFinder()->setPaths([__DIR__.'/../fixtures/empty']);
    View::flushFinderCache();

    expect(fn () => Layout::admin())
        ->toThrow(RuntimeException::class, 'media.layouts.admin');
});

test('home accepts a route name', function () {
    Route::get('/admin', fn () => '')->name('dashboard');
    Route::getRoutes()->refreshNameLookups();

    config()->set('media.routes.home', 'dashboard');

    expect(Layout::home())->toContain('/admin');
});

test('home still accepts a plain URL', function () {
    config()->set('media.routes.home', '/admin');

    expect(Layout::home())->toBe('/admin');
});

test('a config published before the key moved keeps working', function () {
    // routes.home did not exist then; the setting lived under routes.names.home.
    // Losing it silently would send every Dashboard crumb to the site root.
    config()->set('media.routes.home', null);
    config()->set('media.routes.names.home', '/legacy-admin');

    expect(Layout::home())->toBe('/legacy-admin');
});
