<?php

use Kreetancraft\Media\Concerns\HasMediaAttachments;
use Kreetancraft\Media\Support\MediaAvatarResolver;
use Kreetancraft\Media\Tests\Fixtures\Models\Article;

/**
 * The avatar seam used to read only.
 *
 * kreetancraft/laravel-user-management could display an avatar and had no way to
 * set one, so its user forms had no image option and could not gain one. These
 * cover the half that was missing.
 */
beforeEach(function (): void {
    $this->resolver = new MediaAvatarResolver;
});

it('sets an avatar and reads it back', function (): void {
    $user = Article::create(['title' => 'Stands in for a user model']);
    $media = makeMedia();

    $this->resolver->syncFor($user, 'avatar', [$media->id]);

    expect($this->resolver->avatarFor($user))->toContain((string) $media->id)
        ->and($this->resolver->listFor($user))->toHaveCount(1);
});

it('keeps only one avatar, whatever the picker sends', function (): void {
    $user = Article::create(['title' => 'One face']);
    $first = makeMedia();
    $second = makeMedia();

    $this->resolver->syncFor($user, 'avatar', [$first->id, $second->id]);

    expect($this->resolver->listFor($user))->toHaveCount(1);
});

it('clears the avatar when given nothing', function (): void {
    $user = Article::create(['title' => 'Cleared']);
    $this->resolver->syncFor($user, 'avatar', [makeMedia()->id]);

    $this->resolver->syncFor($user, 'avatar', []);

    expect($this->resolver->avatarFor($user))->toBeNull();
});

it('works on a model that does not use the media trait', function (): void {
    // Requiring HasMediaAttachments meant an application whose user model this
    // package does not own could never have an avatar.
    $user = Article::create(['title' => 'Untraited']);

    expect(in_array(
        HasMediaAttachments::class,
        class_uses_recursive($user),
        true
    ))->toBeFalse();

    $this->resolver->syncFor($user, 'avatar', [makeMedia()->id]);

    expect($this->resolver->avatarFor($user))->not->toBeNull();
});
