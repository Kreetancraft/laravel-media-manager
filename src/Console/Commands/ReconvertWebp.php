<?php

namespace Kreetancraft\Media\Console\Commands;

use Illuminate\Console\Command;
use Kreetancraft\Media\Jobs\ConvertMediaToWebp;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ReconvertWebp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:reconvert-webp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retroactively converts existing media items to WebP';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Finding media items missing WebP variants...');

        // cursor(), not get(): the media table carries several longtext
        // columns and loading all of it to filter in PHP is how this used to
        // fall over on a large library.
        $mediaItems = Media::query()
            ->where('collection_name', 'medialibrary')
            ->cursor()
            ->filter(fn ($media) => is_null($media->getCustomProperty('webp_url')));

        $count = $mediaItems->count();

        if ($count === 0) {
            $this->info('All media items already have WebP variants.');

            return Command::SUCCESS;
        }

        $this->info("Dispatching WebP conversion jobs for {$count} items...");

        foreach ($mediaItems as $media) {
            ConvertMediaToWebp::dispatch($media->id);
            $this->line("Dispatched conversion for Media ID: {$media->id} ({$media->file_name})");
        }

        $this->info('All jobs have been dispatched to the queue.');

        return Command::SUCCESS;
    }
}
