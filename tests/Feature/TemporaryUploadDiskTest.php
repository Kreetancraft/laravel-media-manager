<?php

use Illuminate\Support\Facades\Storage;

/**
 * Guards the config that broke every media upload with a 500:
 * "Disk [default] does not have a configured driver."
 *
 * Livewire resolves the temporary upload disk as
 * `config('livewire.temporary_file_upload.disk') ?: config('filesystems.default')`,
 * so any truthy value is used verbatim as a disk name. Setting it to the
 * string 'default' — which is not a configured disk — makes the upload
 * endpoint throw before a component ever sees the file.
 *
 * FileUploadConfiguration::disk() short-circuits to 'tmp-for-tests' while
 * running tests, so this asserts on the configuration itself rather than
 * calling that resolver.
 */
it('leaves the temporary upload disk unset so it falls back to the filesystem default', function (): void {
    $configured = config('livewire.temporary_file_upload.disk');

    expect($configured)->not->toBe('default');

    // Whatever Livewire ends up with must be a real, configured disk.
    $effective = $configured ?: config('filesystems.default');

    expect(config("filesystems.disks.{$effective}"))->not->toBeNull(
        "Livewire would resolve the temporary upload disk to [{$effective}], which is not configured in filesystems.disks."
    );
});

it('can write to the disk livewire would use for temporary uploads', function (): void {
    $disk = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default');

    Storage::disk($disk)->put('livewire-tmp/probe.txt', 'ok');

    expect(Storage::disk($disk)->exists('livewire-tmp/probe.txt'))->toBeTrue();

    Storage::disk($disk)->delete('livewire-tmp/probe.txt');
});
