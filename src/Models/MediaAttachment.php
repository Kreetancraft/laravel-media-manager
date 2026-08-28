<?php

namespace Kreetancraft\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAttachment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'media_id',
        'collection_name',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * The owning model the media is attached to (Trip, Blog post, etc.).
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The underlying Spatie media row.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
