<?php

namespace Kreetancraft\Media\Actions;

use Illuminate\Support\Facades\Log;
use Kreetancraft\Media\Contracts\FolderContract;
use LivewireFilemanager\Filemanager\Models\Folder;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

class DeleteFolderAction
{
    use AsAction;

    public function __construct(
        private readonly FolderContract $folders,
    ) {}

    /**
     * Delete a folder.
     *
     * @throws RuntimeException When attempting to delete the protected root folder.
     */
    public function handle(Folder $folder): void
    {
        if ($folder->isHomeFolder()) {
            throw new RuntimeException('The root folder cannot be deleted.');
        }

        $this->folders->delete($folder);

        Log::info('Media folder deleted', [
            'folder_id' => $folder->id,
            'deleted_by' => auth()->id(),
        ]);
    }
}
