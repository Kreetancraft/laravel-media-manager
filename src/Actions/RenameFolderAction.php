<?php

namespace Kreetancraft\Media\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreetancraft\Media\Contracts\FolderContract;
use LivewireFilemanager\Filemanager\Models\Folder;
use Lorisleiva\Actions\Concerns\AsAction;

class RenameFolderAction
{
    use AsAction;

    public function __construct(
        private readonly FolderContract $folders,
    ) {}

    /**
     * Rename a folder, keeping its slug in sync.
     */
    public function handle(Folder $folder, string $name): Folder
    {
        $name = trim($name);

        $this->folders->update($folder, [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);

        Log::info('Media folder renamed', [
            'folder_id' => $folder->id,
            'name' => $name,
            'updated_by' => auth()->id(),
        ]);

        return $folder;
    }
}
