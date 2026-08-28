<?php

namespace Kreetancraft\Media\Livewire\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use LivewireFilemanager\Filemanager\Models\Folder;

trait MediaPickerState
{
    public bool $multiple = true;

    public string $group = 'default';

    public string $mimeType = 'image/%';

    public ?int $currentFolderId = null;

    public bool $includeSubfolders = false;

    /** @var array<int, int> */
    public array $selected = [];

    public string $search = '';

    public string $filterType = '';

    public string $filterDate = '';

    /** @var array<int, UploadedFile> */
    public array $uploads = [];

    /** @var Collection<int, Folder>|null */
    protected ?Collection $folderCache = null;
}
