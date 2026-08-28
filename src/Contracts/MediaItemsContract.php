<?php

namespace Kreetancraft\Media\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface MediaItemsContract
{
    public function find(int $id): ?Media;

    /** @param array<int, int> $ids */
    public function findWhereIn(array $ids): Collection;

    public function delete(Media $media): void;

    /** @param array<string, mixed> $data */
    public function update(Media $media, array $data): Media;

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function paginateInFolder(Folder $folder, string $search, string $filterType, string $filterDate, int $perPage = 18): LengthAwarePaginator;

    /** @return array<int, int> */
    public function navigationIdsInFolder(Folder $folder, string $search, string $filterType, string $filterDate): array;

    /** @return array<int, array{value: string, label: string}> */
    public function monthsWithUploads(): array;

    /** @return Collection<int, array<string, mixed>> */
    public function subfoldersIn(Folder $folder, string $search): Collection;
}
