<?php

namespace Kreetancraft\Media\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreetancraft\Media\Contracts\FolderContract;
use LivewireFilemanager\Filemanager\Models\Folder;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateFolderAction
{
    use AsAction;

    public function __construct(
        private readonly FolderContract $folders,
    ) {}

    /**
     * Create a new subfolder inside the given parent folder.
     */
    public function handle(Folder $parent, string $name): Folder
    {
        $name = trim($name);

        $folder = $this->folders->create([
            'name' => $name,
            'slug' => Str::slug($name),
            'parent_id' => $parent->id,
        ]);

        Log::info('Media folder created', [
            'folder_id' => $folder->id,
            'parent_id' => $parent->id,
            'created_by' => auth()->id(),
        ]);

        return $folder;
    }
}
