<?php

namespace Kreetancraft\Media\Contracts;

use Illuminate\Support\Collection;
use LivewireFilemanager\Filemanager\Models\Folder;

interface FolderContract
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Folder;

    public function find(int $id): ?Folder;

    public function findOrFail(int $id): Folder;

    public function delete(Folder $folder): void;

    /** @param array<string, mixed> $data */
    public function update(Folder $folder, array $data): Folder;

    /** @return array<int, array{id: int, name: string}> */
    public function breadcrumbTo(Folder $folder): array;

    /** @param array<int, int> $excludedFolderIds */
    public function moveTargetsExcluding(array $excludedFolderIds): Collection;

    /** @return array<int, int> */
    public function folderIdsInParent(int $parentId): array;

    public function slugExistsInParent(string $slug, int $parentId, ?int $excludeId = null): bool;
}
