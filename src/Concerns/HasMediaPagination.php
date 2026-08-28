<?php

namespace Kreetancraft\Media\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Kreetancraft\Media\Contracts\MediaItemsContract;

trait HasMediaPagination
{
    /**
     * Retrieve list of months/years with uploads.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function getUploadedDates(): array
    {
        return app(MediaItemsContract::class)->monthsWithUploads();
    }

    /**
     * Build the paginated, filtered file listing for the current folder.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function getFiles(): LengthAwarePaginator
    {
        return app(MediaItemsContract::class)->paginateInFolder(
            $this->currentFolder,
            $this->search,
            $this->filterType,
            $this->filterDate
        );
    }

    /**
     * Build the subfolder listing for the current folder.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getSubfolders(): Collection
    {
        return app(MediaItemsContract::class)->subfoldersIn($this->currentFolder, $this->search);
    }

    /**
     * Ordered media IDs for the current filter context — used for prev/next
     * navigation inside the details slide-over.
     *
     * @return array<int, int>
     */
    public function getNavigationIds(): array
    {
        return app(MediaItemsContract::class)->navigationIdsInFolder(
            $this->currentFolder,
            $this->search,
            $this->filterType,
            $this->filterDate
        );
    }
}
