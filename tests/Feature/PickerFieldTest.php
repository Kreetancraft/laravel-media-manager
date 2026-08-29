<?php

use Illuminate\Support\Facades\Blade;

/**
 * The ready-made image field other packages point at.
 *
 * kreetancraft/laravel-blog and kreetancraft/laravel-seo ship no image handling
 * and render whatever view their config names, handing it $items, $group and
 * $multiple. This view is the other half of that contract — without it every
 * host has to write one before either package can attach an image.
 */
beforeEach(function (): void {
    // The modal mounts the picker component, which authorizes on mount. A bare
    // render has no authenticated user, and `can:` denies for nobody.
    actingAsSuperAdmin();
});

it('renders chosen images as tiles', function (): void {
    $html = Blade::render(
        "@include('media::picker-field', ['items' => \$items, 'group' => 'featured'])",
        ['items' => [
            ['id' => 1, 'url' => '/storage/a.jpg', 'name' => 'a.jpg'],
            ['id' => 2, 'url' => '/storage/b.jpg', 'name' => 'b.jpg'],
        ]],
    );

    expect($html)->toContain('/storage/a.jpg')
        ->toContain('/storage/b.jpg')
        ->toContain('media-picker-featured');
});

it('shows a placeholder and the empty label when nothing is chosen', function (): void {
    $html = Blade::render(
        "@include('media::picker-field', ['items' => [], 'group' => 'featured', 'emptyLabel' => 'No image yet'])"
    );

    expect($html)->toContain('No image yet')
        ->and($html)->not->toContain('<img');
});

it('renders a pdf as a document link rather than a broken image', function (): void {
    $html = Blade::render(
        "@include('media::picker-field', ['items' => \$items, 'group' => 'docs'])",
        ['items' => [['id' => 9, 'url' => '/storage/brochure.pdf', 'name' => 'brochure.pdf']]],
    );

    expect($html)->toContain('brochure.pdf')
        ->and($html)->not->toContain('<img src="/storage/brochure.pdf"');
});

it('scopes its modal to the group, so two fields on one form do not collide', function (): void {
    // A post edit screen has a featured image and the SEO og:image. Sharing a
    // modal name would make choosing one overwrite the other.
    $featured = Blade::render("@include('media::picker-field', ['items' => [], 'group' => 'blog-post-featured'])");
    $og = Blade::render("@include('media::picker-field', ['items' => [], 'group' => 'seo-og'])");

    expect($featured)->toContain('media-picker-blog-post-featured')
        ->and($og)->toContain('media-picker-seo-og')
        ->and($featured)->not->toContain('media-picker-seo-og');
});

it('renders only the modal when there is no trigger', function (): void {
    // The rich text editor opens the picker from its own toolbar button. A
    // Choose card sitting below the form would do nothing when clicked.
    $html = Blade::render(
        "@include('media::picker-field', ['items' => [], 'group' => 'rich-text-image', 'trigger' => false])"
    );

    expect($html)->toContain('media-picker-rich-text-image')
        ->and($html)->not->toContain('Choose');
});

it('still shows chosen images when there is no trigger... by not showing them', function (): void {
    // With trigger => false nothing visible renders at all, tiles included:
    // the caller is mounting a modal, not displaying a field.
    $html = Blade::render(
        "@include('media::picker-field', ['items' => \$items, 'group' => 'rich-text-image', 'trigger' => false])",
        ['items' => [['id' => 1, 'url' => '/storage/a.jpg', 'name' => 'a.jpg']]],
    );

    expect($html)->not->toContain('/storage/a.jpg')
        ->and($html)->toContain('media-picker-rich-text-image');
});
