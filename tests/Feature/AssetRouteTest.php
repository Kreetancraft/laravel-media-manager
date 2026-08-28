<?php

use Illuminate\Support\Facades\Route;

/**
 * The public asset route.
 *
 * It was dead code when this package was first extracted — FileController came
 * across but its route had lived in the host application's own routes file, so
 * MediaUrl silently fell back to disk URLs and folder-scoped serving never
 * worked. These tests exist so that cannot happen again unnoticed.
 */
test('the asset route is registered by default', function () {
    expect(Route::has(config('media.routes.names.asset')))->toBeTrue();
});

test('the asset route is public, not behind auth', function () {
    $route = Route::getRoutes()->getByName(config('media.routes.names.asset'));

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->not->toContain('auth');
});

test('a request for a path that matches nothing 404s rather than listing', function () {
    seedRolesAndPermissions();

    $this->get('/assets/nothing/here.jpg')->assertNotFound();
});

test('the asset route can be turned off independently of the admin screens', function () {
    // A host may serve its own assets while keeping this package's admin UI.
    config()->set('media.routes.serve_assets', false);
    config()->set('media.routes.register', true);

    expect(config('media.routes.serve_assets'))->toBeFalse()
        ->and(config('media.routes.register'))->toBeTrue();
});

test('admin screens can be turned off while assets keep serving', function () {
    config()->set('media.routes.register', false);
    config()->set('media.routes.serve_assets', true);

    expect(config('media.routes.register'))->toBeFalse()
        ->and(config('media.routes.serve_assets'))->toBeTrue();
});
