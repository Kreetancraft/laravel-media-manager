<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Kreetancraft\Media\Livewire\MediaPicker;
use Livewire\Livewire;
use LivewireFilemanager\Filemanager\Models\Folder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    seedRolesAndPermissions();
    Storage::fake('public');
});

function pickerMedia(string $name = 'pic.jpg', string $mime = 'image/jpeg'): Media
{
    $home = Folder::firstOrCreate(['slug' => 'home'], ['name' => 'Home', 'parent_id' => null]);

    $upload = $mime === 'application/pdf'
        ? UploadedFile::fake()->create($name, 100, $mime)
        : UploadedFile::fake()->image($name);

    $stored = Storage::disk('public')->putFileAs('tmp', $upload, $name);
    $media = $home->addMedia(Storage::disk('public')->path($stored))->toMediaCollection('medialibrary');

    if ($mime === 'application/pdf') {
        $media->mime_type = 'application/pdf';
        $media->save();
    }

    return $media;
}

test('a super admin can select multiple items and confirm', function () {
    actingAsSuperAdmin();
    $a = pickerMedia('a.jpg');
    $b = pickerMedia('b.jpg');

    Livewire::test(MediaPicker::class, ['group' => 'trip-gallery'])
        ->call('toggle', $a->id)
        ->call('toggle', $b->id)
        ->assertSet('selected', [$a->id, $b->id])
        ->call('confirm')
        ->assertDispatched('media-picked', ids: [$a->id, $b->id], group: 'trip-gallery');
});

test('confirm dispatches resolved urls for the picked items', function () {
    actingAsSuperAdmin();
    $a = pickerMedia('a.jpg');

    Livewire::test(MediaPicker::class, ['group' => 'rich-text-image'])
        ->call('toggle', $a->id)
        ->call('confirm')
        ->assertDispatched('media-picked', function (string $event, array $params) use ($a): bool {
            return ($params['group'] ?? null) === 'rich-text-image'
                && ($params['items'][0]['id'] ?? null) === $a->id
                && ! empty($params['items'][0]['url']);
        });
});

test('single-select mode keeps only the last pick', function () {
    actingAsSuperAdmin();
    $a = pickerMedia('a.jpg');
    $b = pickerMedia('b.jpg');

    Livewire::test(MediaPicker::class, ['multiple' => false])
        ->call('toggle', $a->id)
        ->call('toggle', $b->id)
        ->assertSet('selected', [$b->id]);
});

test('toggling an already-selected item deselects it', function () {
    actingAsSuperAdmin();
    $a = pickerMedia('a.jpg');

    Livewire::test(MediaPicker::class)
        ->call('toggle', $a->id)
        ->call('toggle', $a->id)
        ->assertSet('selected', []);
});

test('the picker lists only library images, not documents', function () {
    actingAsSuperAdmin();
    pickerMedia('photo.jpg');
    pickerMedia('doc.pdf', 'application/pdf');

    Livewire::test(MediaPicker::class)
        ->assertViewHas('items', fn ($items) => $items->total() === 1);
});

test('setSelection preloads ids only for the matching group', function () {
    actingAsSuperAdmin();
    $a = pickerMedia('a.jpg');

    Livewire::test(MediaPicker::class, ['group' => 'g'])
        ->call('setSelection', [$a->id], 'other')
        ->assertSet('selected', [])
        ->call('setSelection', [$a->id], 'g')
        ->assertSet('selected', [$a->id]);
});

test('the picker renders a deep folder tree without a per-node folder query explosion', function () {
    actingAsSuperAdmin();

    $home = home();
    $alpha = Folder::create(['name' => 'Alpha', 'slug' => 'alpha', 'parent_id' => $home->id]);
    $a1 = Folder::create(['name' => 'Alpha One', 'slug' => 'alpha-one', 'parent_id' => $alpha->id]);
    Folder::create(['name' => 'Alpha One A', 'slug' => 'alpha-one-a', 'parent_id' => $a1->id]);
    $a2 = Folder::create(['name' => 'Alpha Two', 'slug' => 'alpha-two', 'parent_id' => $alpha->id]);
    Folder::create(['name' => 'Alpha Two B', 'slug' => 'alpha-two-b', 'parent_id' => $a2->id]);
    $bravo = Folder::create(['name' => 'Bravo', 'slug' => 'bravo', 'parent_id' => $home->id]);
    Folder::create(['name' => 'Bravo One', 'slug' => 'bravo-one', 'parent_id' => $bravo->id]);
    Folder::create(['name' => 'Charlie', 'slug' => 'charlie', 'parent_id' => $home->id]);

    $folderCount = Folder::without('children')->count();

    DB::enableQueryLog();

    Livewire::test(MediaPicker::class)
        ->assertSee('Alpha')
        ->assertSee('Alpha One')
        ->assertSee('Alpha Two')
        ->assertSee('Bravo')
        ->assertSee('Charlie');

    $folderQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query) => str_contains($query['query'], 'folders'))
        ->count();

    // Every folder is loaded once into an in-memory map; the tree, ancestor chain
    // and descendant ids are derived from that map. Folder queries must NOT scale
    // with the number of folders (that would be the old recursive `children` N+1).
    expect($folderQueries)->toBeLessThan($folderCount);
});
