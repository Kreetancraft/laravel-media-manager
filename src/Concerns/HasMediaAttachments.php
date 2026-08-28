<?php

namespace Kreetancraft\Media\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kreetancraft\Media\Models\MediaAttachment;

/**
 * Lets any model reference shared library media (Spatie media rows) without
 * owning the files. The same media item can be attached to many models.
 *
 * Unlike Spatie's HasMedia (1:1 ownership), this is a many-to-many link table.
 */
trait HasMediaAttachments
{
    /**
     * Raw attachment rows, ordered for display.
     */
    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'attachable')->orderBy('sort_order');
    }

    /**
     * The attached Media models for a collection, in sort order. Uses the
     * eager-loaded relation when available (`with('mediaAttachments.media')`)
     * so listings don't fire a query per row.
     *
     * @return Collection<int, Media>
     */
    public function attachedMedia(string $collection = 'default'): Collection
    {
        if ($this->relationLoaded('mediaAttachments')) {
            return $this->mediaAttachments
                ->where('collection_name', $collection)
                ->sortBy('sort_order')
                ->pluck('media')
                ->filter()
                ->values();
        }

        // Model::preventLazyLoading() only fires on PROPERTY access, so this
        // fallback is invisible to it — which is exactly how a family of N+1s
        // went unnoticed. Surface it in development instead.
        self::reportUnEagerLoadedAccess($collection);

        return $this->mediaAttachments()
            ->where('collection_name', $collection)
            ->with('media')
            ->get()
            ->pluck('media')
            ->filter()
            ->values();
    }

    /**
     * Attach a single media item (idempotent on the unique key).
     */
    public function attachMedia(int $mediaId, string $collection = 'default', ?int $sortOrder = null): MediaAttachment
    {
        $sortOrder ??= ((int) $this->mediaAttachments()
            ->where('collection_name', $collection)
            ->max('sort_order')) + 1;

        return $this->mediaAttachments()->firstOrCreate(
            ['media_id' => $mediaId, 'collection_name' => $collection],
            ['sort_order' => $sortOrder],
        );
    }

    /**
     * Detach a single media item from a collection.
     */
    public function detachMedia(int $mediaId, string $collection = 'default'): void
    {
        $this->mediaAttachments()
            ->where('collection_name', $collection)
            ->where('media_id', $mediaId)
            ->delete();
    }

    /**
     * Replace the collection's attachments with the given media IDs, in order.
     *
     * @param  array<int, int>  $mediaIds
     */
    public function syncAttachedMedia(array $mediaIds, string $collection = 'default'): void
    {
        DB::transaction(function () use ($mediaIds, $collection) {
            $this->mediaAttachments()->where('collection_name', $collection)->delete();

            foreach (array_values($mediaIds) as $index => $mediaId) {
                $this->mediaAttachments()->create([
                    'media_id' => $mediaId,
                    'collection_name' => $collection,
                    'sort_order' => $index,
                ]);
            }
        });
    }

    /**
     * Boot hook: when the owner is deleted, drop its attachment rows (the
     * shared media itself is left intact for other owners).
     */
    public static function bootHasMediaAttachments(): void
    {
        static::deleting(function ($model) {
            // Skip when the model uses soft deletes and this is only a soft delete.
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->mediaAttachments()->delete();
        });
    }

    /**
     * Warn when attachedMedia() had to query, i.e. the caller forgot
     * ->with('mediaAttachments.media').
     *
     * Silent in production: a log line per row is worse than the N+1.
     */
    protected static function reportUnEagerLoadedAccess(string $collection): void
    {
        if (! config('media.warn_on_lazy_attachments', false)) {
            return;
        }

        if (app()->runningInConsole() || app()->isProduction()) {
            return;
        }

        logger()->warning('attachedMedia() queried without eager loading.', [
            'model' => static::class,
            'collection' => $collection,
            'fix' => "->with('mediaAttachments.media')",
        ]);
    }
}
