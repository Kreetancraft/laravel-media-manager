<?php

namespace Kreetancraft\Media\Repositories;

use Illuminate\Support\Collection;
use Kreetancraft\Media\Contracts\FolderContract;
use LivewireFilemanager\Filemanager\Models\Folder;

class FolderRepository implements FolderContract
{
    /**
     * Build the path breadcrumb from the root down to the given folder.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function breadcrumbTo(Folder $folder): array
    {
        $tree = $this->folderMap();

        $breadcrumb = [];
        $currentId = $folder->id;

        while ($currentId !== null) {
            $node = $tree[$currentId] ?? null;

            if ($node === null) {
                break;
            }

            array_unshift($breadcrumb, [
                'id' => $node['id'],
                'name' => $node['name'],
            ]);

            $currentId = $node['parent_id'];
        }

        return $breadcrumb;
    }

    /**
     * All folders eligible as a move target — excluding the given folders and
     * any of their descendants (you cannot move a folder into itself).
     *
     * @param  array<int, int>  $excludedFolderIds
     * @return Collection<int, Folder>
     */
    public function moveTargetsExcluding(array $excludedFolderIds): Collection
    {
        $tree = $this->folderMap();
        $excluded = array_flip($excludedFolderIds);

        $descendantIds = [];
        foreach ($excludedFolderIds as $excludedId) {
            $descendantIds += $this->descendantIdsOf($excludedId, $tree);
        }
        $excluded += $descendantIds;

        $query = Folder::query()
            ->without('children')
            ->orderBy('name');

        if ($excluded !== []) {
            $query->whereNotIn('id', array_keys($excluded));
        }

        return $query->get()
            ->each(function (Folder $folder) use ($tree) {
                $folder->setAttribute('depth', $this->depthOf($folder->id, $tree));
            });
    }

    /**
     * Lazy-load every folder once (without the recursive `children` relation)
     * and return an id => {id, name, parent_id} map for in-memory traversal.
     *
     * @return array<int, array{id: int, name: string, parent_id: ?int}>
     */
    private function folderMap(): array
    {
        return Folder::query()
            ->without('children')
            ->get(['id', 'name', 'parent_id'])
            ->mapWithKeys(fn (Folder $folder) => [
                $folder->id => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'parent_id' => $folder->parent_id,
                ],
            ])
            ->all();
    }

    /**
     * Collect every descendant id of a folder from the in-memory map.
     *
     * @param  array<int, array{id: int, name: string, parent_id: ?int}>  $tree
     * @return array<int, int>
     */
    private function descendantIdsOf(int $folderId, array $tree): array
    {
        $result = [];
        $queue = [$folderId];

        while ($queue !== []) {
            $current = array_pop($queue);

            foreach ($tree as $node) {
                if ($node['parent_id'] === $current) {
                    $result[$node['id']] = $node['id'];
                    $queue[] = $node['id'];
                }
            }
        }

        return $result;
    }

    /**
     * Depth of a folder computed from the in-memory map (no DB round-trips).
     *
     * @param  array<int, array{id: int, name: string, parent_id: ?int}>  $tree
     */
    private function depthOf(int $folderId, array $tree): int
    {
        $depth = 0;
        $currentId = $tree[$folderId]['parent_id'] ?? null;

        while ($currentId !== null) {
            $depth++;
            $currentId = $tree[$currentId]['parent_id'] ?? null;
        }

        return $depth;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Folder
    {
        return Folder::create($data);
    }

    public function findOrFail(int $id): Folder
    {
        return Folder::without('children')->findOrFail($id);
    }

    public function delete(Folder $folder): void
    {
        $folder->delete();
    }

    /** @param array<string, mixed> $data */
    public function update(Folder $folder, array $data): Folder
    {
        $folder->update($data);

        return $folder;
    }

    public function find(int $id): ?Folder
    {
        return Folder::without('children')->find($id);
    }

    /** @return array<int, int> */
    public function folderIdsInParent(int $parentId): array
    {
        return Folder::where('parent_id', $parentId)->pluck('id')->toArray();
    }

    public function slugExistsInParent(string $slug, int $parentId, ?int $excludeId = null): bool
    {
        $query = Folder::where('slug', $slug)->where('parent_id', $parentId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
