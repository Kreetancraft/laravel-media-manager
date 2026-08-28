<?php

namespace Kreetancraft\Media\Repositories;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Kreetancraft\Media\Support\UserResolver;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MediaRepository implements MediaItemsContract
{
    /**
     * Escape LIKE wildcards (% and _) to prevent SQL injection via search.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * Build a synthetic Request carrying the filter values in Spatie Query
     * Builder's expected `filter[...]` shape. Empty values are dropped so no
     * filter is applied when nothing is selected.
     */
    private function buildRequest(array $params): Request
    {
        $params = array_filter($params, fn ($value) => $value !== null && $value !== '');

        if ($params === []) {
            return Request::create('/', 'GET', []);
        }

        return Request::create('/', 'GET', ['filter' => $params]);
    }

    /**
     * Build the paginated, filtered file listing for a folder with pre-computed
     * display URLs and uploader names to avoid N+1 queries in the view.
     *
     * Filtering/sorting is delegated to Spatie Query Builder (mirroring the
     * host application).
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateInFolder(Folder $folder, string $search, string $filterType, string $filterDate, int $perPage = 18): LengthAwarePaginator
    {
        $request = $this->buildRequest([
            'search' => $search,
            'type' => $filterType !== 'all' ? $filterType : null,
            'date' => $filterDate !== 'all' ? $filterDate : null,
        ]);

        $files = QueryBuilder::for(
            Media::query()
                ->where('model_type', Folder::class)
                ->where('model_id', $folder->id)
                ->where('collection_name', 'medialibrary'),
            $request
        )
            ->allowedFilters(
                AllowedFilter::callback('search', function ($query, $value) {
                    $escaped = $this->escapeLike((string) $value);
                    $query->where(function ($q) use ($escaped) {
                        $q->where('name', 'like', "%{$escaped}%")
                            ->orWhere('file_name', 'like', "%{$escaped}%");
                    });
                }),
                AllowedFilter::callback('type', function ($query, $value) {
                    if ($value === 'images') {
                        $query->where('mime_type', 'like', 'image/%');
                    } elseif ($value === 'documents') {
                        $query->where('mime_type', 'application/pdf');
                    } elseif ($value === 'archives') {
                        $query->where(function ($q) {
                            $q->where('mime_type', 'like', '%zip%')
                                ->orWhere('mime_type', 'like', '%rar%');
                        });
                    }
                }),
                AllowedFilter::callback('date', fn ($query, $value) => $query->where('created_at', 'like', "{$value}%")),
            )
            ->defaultSort('id')
            ->paginate($perPage);

        $userIds = $files->getCollection()->map(fn ($file) => $file->getCustomProperty('user_id'))->filter()->unique();
        $uploaders = UserResolver::keyedById($userIds);
        $currentFolderPath = buildFolderPath($folder->id);

        return $files->through(function ($file) use ($currentFolderPath, $uploaders) {
            $isImage = str_starts_with($file->mime_type, 'image/');
            $relativeAssetPath = $currentFolderPath ? $currentFolderPath.'/'.$file->file_name : $file->file_name;
            $assetUrl = route(config('media.routes.names.asset', 'media.asset'), ['path' => $relativeAssetPath]);

            $webpUrl = $file->getCustomProperty('webp_url');
            $displayUrl = $isImage && $webpUrl ? $assetUrl.'?webp=1' : $assetUrl;
            $thumbnailUrl = $isImage && $file->hasGeneratedConversion('thumbnail') ? $assetUrl.'?conversion=thumbnail' : $displayUrl;

            $uploaderId = $file->getCustomProperty('user_id');
            $uploader = $uploaderId ? ($uploaders[$uploaderId] ?? null) : null;
            $authorName = UserResolver::nameFor($uploader);

            return [
                'id' => $file->id,
                'name' => $file->name,
                'file_name' => $file->file_name,
                'human_readable_size' => $file->human_readable_size,
                'mime_type' => $file->mime_type,
                'created_at_formatted' => $file->created_at->format('M d, Y H:i:s'),
                'created_at_date' => $file->created_at->format('M d, Y'),
                'assetUrl' => $assetUrl,
                'webpUrl' => $webpUrl,
                'isImage' => $isImage,
                'displayUrl' => $displayUrl,
                'thumbnailUrl' => $thumbnailUrl,
                'author' => $authorName,
            ];
        });
    }

    /**
     * Build the subfolder listing for a folder with pre-computed element
     * counts to avoid N+1 queries in the view.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function subfoldersIn(Folder $folder, string $search): Collection
    {
        $request = $this->buildRequest([
            'search' => $search !== '' ? $search : null,
        ]);

        $folders = QueryBuilder::for(
            Folder::where('parent_id', $folder->id)
                ->without('children')
                ->withCount([
                    'children',
                    'media as media_count' => fn ($query) => $query->where('collection_name', 'medialibrary'),
                ]),
            $request
        )
            ->allowedFilters(
                AllowedFilter::callback('search', function ($query, $value) {
                    $escaped = $this->escapeLike((string) $value);
                    $query->where('name', 'like', "%{$escaped}%");
                }),
            )
            ->defaultSort('name')
            ->get();

        return $folders->map(function ($folder) {
            $totalCount = $folder->children_count + $folder->media_count;
            $elementsText = trans_choice('livewire-filemanager::filemanager.elements', $totalCount, ['value' => $totalCount]);

            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'slug' => $folder->slug,
                'elements' => $elementsText,
                'isHome' => $folder->isHomeFolder(),
            ];
        });
    }

    /**
     * List of months/years that contain uploads (across the whole library).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function monthsWithUploads(): array
    {
        return Media::query()
            ->where('collection_name', 'medialibrary')
            ->selectRaw('substr(created_at, 1, 7) as value')
            ->distinct()
            ->pluck('value')
            ->filter()
            ->sortDesc()
            ->values()
            ->map(fn ($value) => [
                'value' => $value,
                'label' => Carbon::createFromFormat('Y-m', $value)->format('F Y'),
            ])
            ->toArray();
    }

    /**
     * Ordered media IDs for the current filter context — used for prev/next
     * navigation inside the details slide-over.
     *
     * @return array<int, int>
     */
    public function navigationIdsInFolder(Folder $folder, string $search, string $filterType, string $filterDate): array
    {
        $request = $this->buildRequest([
            'search' => $search,
            'type' => $filterType !== 'all' ? $filterType : null,
            'date' => $filterDate !== 'all' ? $filterDate : null,
        ]);

        return QueryBuilder::for(
            Media::query()
                ->where('model_type', Folder::class)
                ->where('model_id', $folder->id)
                ->where('collection_name', 'medialibrary'),
            $request
        )
            ->allowedFilters(
                AllowedFilter::callback('search', function ($query, $value) {
                    $escaped = $this->escapeLike((string) $value);
                    $query->where(function ($q) use ($escaped) {
                        $q->where('name', 'like', "%{$escaped}%")
                            ->orWhere('file_name', 'like', "%{$escaped}%");
                    });
                }),
                AllowedFilter::callback('type', function ($query, $value) {
                    if ($value === 'images') {
                        $query->where('mime_type', 'like', 'image/%');
                    } elseif ($value === 'documents') {
                        $query->where('mime_type', 'application/pdf');
                    } elseif ($value === 'archives') {
                        $query->where(function ($q) {
                            $q->where('mime_type', 'like', '%zip%')
                                ->orWhere('mime_type', 'like', '%rar%');
                        });
                    }
                }),
                AllowedFilter::callback('date', fn ($query, $value) => $query->where('created_at', 'like', "{$value}%")),
            )
            ->defaultSort('id')
            ->pluck('id')
            ->toArray();
    }

    public function find(int $id): ?Media
    {
        return Media::find($id);
    }

    /** @param array<int, int> $ids */
    public function findWhereIn(array $ids): Collection
    {
        return Media::whereIn('id', $ids)->get();
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }

    /** @param array<string, mixed> $data */
    public function update(Media $media, array $data): Media
    {
        $media->update($data);

        return $media;
    }
}
