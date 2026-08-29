<?php

namespace Kreetancraft\Media\Livewire;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Kreetancraft\Media\Actions\UploadMediaAction;
use Kreetancraft\Media\Support\MediaImageResolver;
use Kreetancraft\Media\Support\UploadRules;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use LivewireFilemanager\Filemanager\Models\Folder;

/**
 * Upload one image and attach it, without opening the library.
 *
 * The picker is the wrong tool for a profile page: it browses every file in the
 * library and is gated on `viewAny` for media, so letting someone set their own
 * picture meant showing them everyone's files and granting a permission they
 * have no other use for. This asks for a file and nothing else.
 *
 * Authorization follows the *subject*, not the library: you may always set your
 * own, and setting someone else's is `update` on them. No media permission is
 * involved either way, which is the point.
 */
class AvatarUploader extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $modelType = '';

    #[Locked]
    public int|string|null $modelId = null;

    #[Locked]
    public string $collection = 'avatar';

    #[Locked]
    public string $group = 'user-avatar';

    public $upload = null;

    public ?string $currentUrl = null;

    public function mount(
        Model $model,
        ?string $collection = null,
        ?string $group = null,
    ): void {
        $this->modelType = $model::class;
        $this->modelId = $model->getKey();
        $this->collection = $collection ?? (string) config('media.avatar.collection', 'avatar');
        $this->group = $group ?? 'user-avatar';

        $this->authorizeSubject($model);

        $this->currentUrl = $this->resolver()->urlFor($model, $this->collection);
    }

    public function updatedUpload(): void
    {
        $model = $this->model();

        $this->authorizeSubject($model);

        try {
            // Images only. The library accepts documents; a profile picture
            // that turns out to be a PDF is not a picture.
            $this->validate([
                'upload' => [
                    'required',
                    File::image()->max(UploadRules::maxSizeKb()),
                ],
            ]);
        } catch (ValidationException $e) {
            $this->upload = null;

            Flux::toast(variant: 'error', text: $e->validator->errors()->first());

            return;
        }

        $folder = $this->folder();

        if ($folder === null) {
            $this->upload = null;

            Flux::toast(variant: 'error', text: __('No media folder is available to store the image.'));

            return;
        }

        $media = UploadMediaAction::run($folder, $this->upload, Auth::id());

        $resolver = $this->resolver();
        $resolver->syncFor($model, $this->collection, [$media->id]);

        $this->upload = null;
        $this->currentUrl = $resolver->urlFor($model, $this->collection);

        // The same event the picker emits, so anything already listening for a
        // chosen image does not care which way it arrived.
        $this->dispatch(
            'media-picked',
            ids: [$media->id],
            group: $this->group,
            items: [['id' => $media->id, 'url' => $this->currentUrl, 'name' => $media->name]],
        );

        Flux::toast(variant: 'success', text: __('Image uploaded.'));
    }

    public function remove(): void
    {
        $model = $this->model();

        $this->authorizeSubject($model);

        $this->resolver()->syncFor($model, $this->collection, []);

        $this->currentUrl = null;

        $this->dispatch('media-picked', ids: [], group: $this->group, items: []);

        Flux::toast(variant: 'success', text: __('Image removed.'));
    }

    public function render(): View
    {
        return view('media::livewire.avatar-uploader');
    }

    /**
     * You may always set your own; someone else's is an update on them.
     *
     * Deliberately not a media permission: a person setting their own picture
     * should not need rights over the library.
     */
    private function authorizeSubject(Model $model): void
    {
        if ($model->getKey() === Auth::user()?->getKey()
            && $model->getMorphClass() === Auth::user()?->getMorphClass()) {
            return;
        }

        abort_unless(Gate::allows('update', $model), 403);
    }

    private function model(): Model
    {
        /** @var class-string<Model> $class */
        $class = $this->modelType;

        $model = $class::find($this->modelId);

        abort_if($model === null, 404);

        return $model;
    }

    private function resolver(): MediaImageResolver
    {
        return app(MediaImageResolver::class);
    }

    /**
     * Where an uploaded avatar is stored: a named folder when configured,
     * otherwise the library root.
     */
    private function folder(): ?Folder
    {
        $name = config('media.avatar.folder');

        if (is_string($name) && $name !== '') {
            $existing = Folder::query()->where('name', $name)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return Folder::query()->whereNull('parent_id')->first();
    }
}
