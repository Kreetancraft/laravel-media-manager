<?php

namespace Kreetancraft\Media\Livewire;

use Flux\Flux;
use Illuminate\Support\Collection;
use Kreetancraft\Media\Actions\CopyUrlAction;
use Kreetancraft\Media\Actions\GenerateAltTextAction;
use Kreetancraft\Media\Actions\GetMediaUsageAction;
use Kreetancraft\Media\Actions\UpdateMediaMetadataAction;
use Kreetancraft\Media\Contracts\MediaItemsContract;
use Kreetancraft\Media\Support\UserResolver;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaDetails extends Component
{
    public ?int $mediaId = null;

    /**
     * Ordered media IDs on the current page — used for prev/next navigation.
     *
     * @var array<int, int>
     */
    public array $mediaIds = [];

    /**
     * Editable metadata buffer (alt text, title, caption, description).
     *
     * @var array<string, string>
     */
    public array $metadata = [
        'title' => '',
        'alt_text' => '',
        'caption' => '',
        'description' => '',
    ];

    /**
     * Lightweight display data for the hero panel (kept in sync from the media row).
     *
     * @var array<string, mixed>
     */
    public array $file = [];

    /**
     * "Used in" locations resolved from the media_attachments pivot.
     *
     * @var array<int, array{type: string, id: int|string, title: string, collection: string, url: ?string}>
     */
    public array $usage = [];

    public function mount(?int $mediaId = null, array $mediaIds = []): void
    {
        $this->mediaIds = $mediaIds;
        if ($mediaId !== null) {
            $this->loadMedia($mediaId);
        }
    }

    /**
     * The gallery opens the slide-over by setting the media id directly.
     */
    public function open(int $mediaId): void
    {
        $this->loadMedia($mediaId);
    }

    #[On('media-details:open')]
    public function openFromEvent(int $mediaId): void
    {
        $this->loadMedia($mediaId);
    }

    public function loadMedia(int $mediaId): void
    {
        $this->mediaId = $mediaId;
        $media = app(MediaItemsContract::class)->find($mediaId);

        if ($media === null) {
            $this->file = [];
            $this->usage = [];

            return;
        }

        $isImage = str_starts_with($media->mime_type, 'image/');
        $folderPath = buildFolderPath($media->model_id);
        $relativeAssetPath = $folderPath ? $folderPath.'/'.$media->file_name : $media->file_name;
        $assetUrl = route(config('media.routes.names.asset', 'media.asset'), ['path' => $relativeAssetPath]);
        $webpUrl = $media->getCustomProperty('webp_url');
        $displayUrl = $isImage && $webpUrl ? $assetUrl.'?webp=1' : $assetUrl;

        $uploaderId = $media->getCustomProperty('user_id');
        $uploader = UserResolver::find($uploaderId);

        $this->file = [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'human_readable_size' => $media->human_readable_size,
            'created_at' => $media->created_at->format('M d, Y H:i'),
            'author' => $uploader?->name ?? __('Unknown'),
            'assetUrl' => $assetUrl,
            'webpUrl' => $webpUrl ?? '',
            'isImage' => $isImage,
            'displayUrl' => $displayUrl,
        ];

        $this->metadata = [
            'title' => $media->name,
            'alt_text' => $media->getCustomProperty('alt_text', ''),
            'caption' => $media->getCustomProperty('caption', ''),
            'description' => $media->getCustomProperty('description', ''),
        ];

        $this->usage = (new GetMediaUsageAction)($media);
    }

    /**
     * Persist metadata edits as they happen.
     */
    public function updatedMetadata(mixed $value, string $key): void
    {
        $this->authorize('manage-media');

        if ($this->mediaId === null) {
            return;
        }

        $media = app(MediaItemsContract::class)->find($this->mediaId);
        if ($media === null) {
            return;
        }

        UpdateMediaMetadataAction::run($media, $key, (string) $value);
    }

    public function generateAltText(): void
    {
        $this->authorize('manage-media');

        if ($this->mediaId === null) {
            return;
        }

        $media = app(MediaItemsContract::class)->find($this->mediaId);
        if ($media === null) {
            return;
        }

        $altText = GenerateAltTextAction::run($media);
        $this->metadata['alt_text'] = $altText;

        Flux::toast(variant: 'success', text: __('Alternative text generated successfully.'));
    }

    /**
     * Copy the public URL (optionally for a responsive size) to the clipboard.
     */
    public function copyUrl(?string $conversion = null): void
    {
        if ($this->mediaId === null) {
            return;
        }

        $media = app(MediaItemsContract::class)->find($this->mediaId);
        if ($media === null) {
            return;
        }

        $url = (new CopyUrlAction)($media, $conversion);
        $this->dispatch('clipboard-copy', text: $url);
        Flux::toast(variant: 'success', text: __('URL copied to clipboard!'));
    }

    public function copyMarkdown(): void
    {
        if ($this->mediaId === null) {
            return;
        }

        $media = app(MediaItemsContract::class)->find($this->mediaId);
        if ($media === null) {
            return;
        }

        $markdown = (new CopyUrlAction)->markdown($media);
        $this->dispatch('clipboard-copy', text: $markdown);
        Flux::toast(variant: 'success', text: __('Markdown copied to clipboard!'));
    }

    public function navigateToAdjacentMedia(string $direction): void
    {
        if ($this->mediaId === null || $this->mediaIds === []) {
            return;
        }

        $currentIndex = array_search($this->mediaId, $this->mediaIds);
        if ($currentIndex === false) {
            return;
        }

        $nextIndex = $direction === 'next' ? $currentIndex + 1 : $currentIndex - 1;

        if (isset($this->mediaIds[$nextIndex])) {
            $this->loadMedia($this->mediaIds[$nextIndex]);
        }
    }

    public function deleteCurrent(): void
    {
        $this->authorize('manage-media');

        if ($this->mediaId === null) {
            return;
        }

        $media = app(MediaItemsContract::class)->find($this->mediaId);
        $id = $this->mediaId;
        $this->mediaId = null;
        $this->file = [];

        Flux::modal('media-details')->close();

        if ($media !== null) {
            app(MediaItemsContract::class)->delete($media);
        }

        $this->dispatch('media-deleted', id: $id);
        Flux::toast(variant: 'success', text: __('File deleted.'));
    }

    public function render()
    {
        return view('media::livewire.details');
    }
}
