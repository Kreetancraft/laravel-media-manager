<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index `media.collection_name`.
 *
 * The gallery, the picker and `media:reconvert-webp` all filter on it, and it
 * was unindexed — a full scan of a table that carries several longtext columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('media-library.table_name', 'media');

        if (! Schema::hasColumn($table, 'collection_name')) {
            return;
        }

        if (Schema::hasIndex($table, 'media_collection_name_index')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->index('collection_name', 'media_collection_name_index');
        });
    }

    public function down(): void
    {
        $table = config('media-library.table_name', 'media');

        if (Schema::hasIndex($table, 'media_collection_name_index')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex('media_collection_name_index');
            });
        }
    }
};
