<?php

use Kreetancraft\Support\ApiSurface;

/**
 * The compatibility promise, made enforceable.
 *
 * This package broke its public API twice in three releases. Each break was
 * defensible on its own, but nothing stopped the next one — and a promise with
 * no mechanism is a wish.
 *
 * This walks every public class and method and compares the result to a
 * committed snapshot. Renaming a method, changing a parameter or narrowing a
 * return type fails here. That does not forbid the change; it forces it to be
 * deliberate and puts it in the diff where a reviewer sees it.
 *
 * Regenerate deliberately after deciding a break is warranted:
 *
 *     UPDATE_API_SURFACE=1 vendor/bin/pest --filter=ApiSurface
 */
test('the public API matches its committed snapshot', function () {
    $snapshotPath = __DIR__.'/../api-surface.json';
    $current = ApiSurface::for(__DIR__.'/../../src', 'Kreetancraft\Media');

    if (getenv('UPDATE_API_SURFACE')) {
        file_put_contents($snapshotPath, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        expect(true)->toBeTrue();

        return;
    }

    expect(file_exists($snapshotPath))->toBeTrue(
        'No API snapshot committed yet. Run: UPDATE_API_SURFACE=1 vendor/bin/pest --filter=ApiSurface'
    );

    $committed = json_decode(file_get_contents($snapshotPath), true, 512, JSON_THROW_ON_ERROR);

    $removed = array_diff_key($committed, $current);
    $changed = [];

    foreach ($current as $symbol => $signature) {
        if (isset($committed[$symbol]) && $committed[$symbol] !== $signature) {
            $changed[$symbol] = "was: {$committed[$symbol]}  now: {$signature}";
        }
    }

    expect($removed)->toBe([], 'Public API removed — a breaking change:'.PHP_EOL.implode(PHP_EOL, array_keys($removed)));
    expect($changed)->toBe([], 'Public signatures changed — a breaking change:'.PHP_EOL.implode(PHP_EOL, $changed));
});

test('additions are allowed without ceremony', function () {
    // The promise is one-way: adding to the surface is always fine, and must not
    // require regenerating the snapshot to get a green build.
    $snapshotPath = __DIR__.'/../api-surface.json';

    if (! file_exists($snapshotPath)) {
        $this->markTestSkipped('No snapshot yet.');
    }

    $committed = json_decode(file_get_contents($snapshotPath), true, 512, JSON_THROW_ON_ERROR);
    $current = ApiSurface::for(__DIR__.'/../../src', 'Kreetancraft\Media');

    // Anything new is simply not asserted on.
    expect(array_diff_key($current, $committed))->toBeArray();
});
