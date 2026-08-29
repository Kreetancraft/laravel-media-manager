<?php

use Illuminate\Support\Facades\DB;
use Kreetancraft\Media\Concerns\HasMediaAttachments;
use Kreetancraft\Media\Models\MediaAttachment;
use Kreetancraft\Media\Support\MediaImageResolver;
use Kreetancraft\Media\Tests\Fixtures\Models\Article;

/**
 * The seam that lets packages shipping no image handling still show images.
 *
 * The important property under test is that the model does NOT have to use
 * HasMediaAttachments: those packages cannot apply this package's trait without
 * making it a hard dependency, which is the whole reason this exists.
 */
beforeEach(function (): void {
    $this->resolver = new MediaImageResolver;
});

it('resolves images for a model that does not use the trait', function (): void {
    $article = Article::create(['title' => 'Untraited']);

    expect(in_array(
        HasMediaAttachments::class,
        class_uses_recursive($article),
        true
    ))->toBeFalse();

    $media = attachMediaTo($article, 'featured');

    expect($this->resolver->urlFor($article, 'featured'))->toContain((string) $media->id);
});

it('returns null for a collection with nothing in it', function (): void {
    $article = Article::create(['title' => 'Empty']);

    expect($this->resolver->urlFor($article, 'featured'))->toBeNull()
        ->and($this->resolver->listFor($article, 'featured'))->toBe([]);
});

it('keeps collections apart', function (): void {
    $article = Article::create(['title' => 'Two collections']);
    attachMediaTo($article, 'featured');
    attachMediaTo($article, 'gallery');

    expect($this->resolver->listFor($article, 'featured'))->toHaveCount(1)
        ->and($this->resolver->listFor($article, 'gallery'))->toHaveCount(1);
});

it('syncs a collection, replacing what was there', function (): void {
    $article = Article::create(['title' => 'Syncing']);
    $first = attachMediaTo($article, 'featured');
    $second = makeMedia();

    $this->resolver->syncFor($article, 'featured', [$second->id]);

    $ids = MediaAttachment::where('attachable_id', $article->id)
        ->where('collection_name', 'featured')
        ->pluck('media_id');

    expect($ids)->toHaveCount(1)
        ->and($ids->first())->toBe($second->id)
        ->and($ids->first())->not->toBe($first->id);
});

it('forgets what it cached when a collection is synced', function (): void {
    // Stale reads here would show an editor the image they just replaced.
    $article = Article::create(['title' => 'Cache busting']);
    attachMediaTo($article, 'featured');

    $this->resolver->listFor($article, 'featured');

    $this->resolver->syncFor($article, 'featured', []);

    expect($this->resolver->listFor($article, 'featured'))->toBe([]);
});

it('preloads a whole page in one query', function (): void {
    // The point of the seam: without preloading, resolving per model is an N+1.
    $articles = collect(range(1, 5))->map(function (int $n) {
        $article = Article::create(['title' => 'Post '.$n]);
        attachMediaTo($article, 'featured');

        return $article;
    });

    DB::enableQueryLog();
    $this->resolver->preload($articles, 'featured');
    $preloadQueries = count(DB::getQueryLog());

    DB::flushQueryLog();
    $articles->each(fn ($a) => $this->resolver->urlFor($a, 'featured'));
    $afterWarm = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($preloadQueries)->toBeLessThanOrEqual(2)
        ->and($afterWarm)->toBe(0);
});

it('does not re-query models it has already warmed', function (): void {
    $article = Article::create(['title' => 'Warm once']);
    attachMediaTo($article, 'featured');

    $this->resolver->preload([$article], 'featured');

    DB::enableQueryLog();
    $this->resolver->preload([$article], 'featured');
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBe(0);
});
