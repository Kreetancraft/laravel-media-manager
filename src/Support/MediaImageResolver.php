<?php

namespace Kreetancraft\Media\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kreetancraft\Media\Models\MediaAttachment;

/**
 * Supplies images to packages that ship none.
 *
 * kreetancraft/laravel-seo and kreetancraft/laravel-blog deliberately handle no
 * images: a PHP class cannot conditionally `use` a trait, so pulling in this
 * package's trait would make it a hard dependency and a missing one a fatal
 * error rather than a missing feature. They ask a configured resolver instead:
 *
 *     // config/blog.php  (and config/seo.php)
 *     'image_resolver' => \Kreetancraft\Media\Support\MediaImageResolver::class,
 *
 * Neither package declares a dependency on this one, and this one declares none
 * on them — the seam is a config value on both sides.
 *
 * Deliberately does NOT require the calling model to use HasMediaAttachments.
 * `media_attachments` is polymorphic, so it can be read by type and key alone,
 * which is what lets a package's own models resolve images without inheriting
 * anything from here.
 */
class MediaImageResolver
{
    /**
     * URLs already resolved by preload(), keyed by "type:id:collection".
     *
     * @var array<string, list<array{id: int, url: ?string, name: ?string}>>
     */
    private array $warmed = [];

    /**
     * URL of the first image in a collection, or null.
     */
    public function urlFor(Model $model, string $collection): ?string
    {
        return $this->listFor($model, $collection)[0]['url'] ?? null;
    }

    /**
     * Every image in a collection, shaped for a picker.
     *
     * @return list<array{id: int, url: ?string, name: ?string}>
     */
    public function listFor(Model $model, string $collection): array
    {
        $key = $this->key($model, $collection);

        if (array_key_exists($key, $this->warmed)) {
            return $this->warmed[$key];
        }

        return $this->warmed[$key] = $this->fetch([$model], $collection)[$key] ?? [];
    }

    /**
     * Attach exactly these media to a collection, in order, detaching the rest.
     *
     * @param  list<int|string>  $ids
     */
    public function syncFor(Model $model, string $collection, array $ids): void
    {
        DB::transaction(function () use ($model, $collection, $ids): void {
            MediaAttachment::query()
                ->where('attachable_type', $model->getMorphClass())
                ->where('attachable_id', $model->getKey())
                ->where('collection_name', $collection)
                ->delete();

            foreach (array_values($ids) as $index => $mediaId) {
                MediaAttachment::create([
                    'attachable_type' => $model->getMorphClass(),
                    'attachable_id' => $model->getKey(),
                    'media_id' => (int) $mediaId,
                    'collection_name' => $collection,
                    'sort_order' => $index,
                ]);
            }
        });

        unset($this->warmed[$this->key($model, $collection)]);
    }

    /**
     * Warm a whole page of models in one query.
     *
     * Resolving per model is an N+1 by construction, which is the cost of not
     * using a relation. A listing calls this once and the rest is free.
     *
     * @param  iterable<Model>  $models
     */
    public function preload(iterable $models, string $collection): void
    {
        $models = $models instanceof Collection ? $models->all() : iterator_to_array($models, false);

        $pending = array_filter(
            $models,
            fn (Model $model) => ! array_key_exists($this->key($model, $collection), $this->warmed)
        );

        if ($pending === []) {
            return;
        }

        $rows = $this->fetch($pending, $collection);

        foreach ($pending as $model) {
            $key = $this->key($model, $collection);
            $this->warmed[$key] = $rows[$key] ?? [];
        }
    }

    /**
     * @param  list<Model>  $models
     * @return array<string, list<array{id: int, url: ?string, name: ?string}>>
     */
    private function fetch(array $models, string $collection): array
    {
        if ($models === []) {
            return [];
        }

        // Grouped by morph class: a mixed set would otherwise need one query
        // per type anyway, and grouping keeps each `whereIn` on a single index.
        $byType = [];

        foreach ($models as $model) {
            $byType[$model->getMorphClass()][] = $model->getKey();
        }

        $resolved = [];

        foreach ($byType as $type => $ids) {
            $attachments = MediaAttachment::query()
                ->where('attachable_type', $type)
                ->whereIn('attachable_id', $ids)
                ->where('collection_name', $collection)
                ->with('media')
                ->orderBy('sort_order')
                ->get();

            foreach ($attachments as $attachment) {
                if ($attachment->media === null) {
                    continue;
                }

                $resolved[$type.':'.$attachment->attachable_id.':'.$collection][] = [
                    'id' => (int) $attachment->media->id,
                    'url' => MediaUrl::publicFor($attachment->media),
                    'name' => $attachment->media->name,
                ];
            }
        }

        return $resolved;
    }

    private function key(Model $model, string $collection): string
    {
        return $model->getMorphClass().':'.$model->getKey().':'.$collection;
    }
}
