<?php

use Kreetancraft\Media\Models\MediaAttachment;

/**
 * This package contributes a sidebar link without depending on whatever renders
 * it. The whole mechanism is a container tag, so these tests run exactly as they
 * would on an install where nothing collects it — which is the point.
 */
function contributedItems(): array
{
    $items = [];

    foreach (app()->tagged('admin.navigation') as $contribution) {
        $items = array_merge($items, array_is_list($contribution) ? $contribution : [$contribution]);
    }

    return $items;
}

test('a sidebar link is contributed under the shared tag', function () {
    expect(contributedItems())->toHaveCount(1);
});

test('the link points at the gallery route and is gated by the policy', function () {
    $item = contributedItems()[0];

    expect($item['route'])->toBe('admin.media')
        ->and($item['ability'])->toBe('viewAny')
        ->and($item['model'])->toBe(MediaAttachment::class);
});

test('the link asks the same question the route asks', function () {
    // routes/web.php guards the group with `can:viewAny,MediaAttachment`. If
    // these ever diverge the sidebar shows a link to a 403.
    $routes = file_get_contents(__DIR__.'/../../routes/web.php');
    $item = contributedItems()[0];

    expect($routes)->toContain('can:'.$item['ability'].',');
});

test('the contribution costs nothing when no one collects the tag', function () {
    // Never resolved unless something iterates the tag. Binding alone must not
    // touch config, the database or the router.
    expect(app()->bound('media.navigation.items'))->toBeTrue()
        ->and(app()->resolved('media.navigation.items'))->toBeFalse();
});
