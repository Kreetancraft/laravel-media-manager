<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->foreignId('media_id')
                ->constrained(config('media-library.table_name', 'media'))
                ->cascadeOnDelete();
            $table->string('collection_name')->default('default');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['attachable_type', 'attachable_id', 'media_id', 'collection_name'],
                'media_attachments_unique',
            );
            $table->index(
                ['attachable_type', 'attachable_id', 'collection_name'],
                'media_attachments_lookup',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_attachments');
    }
};
