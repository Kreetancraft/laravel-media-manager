@props([
    'group' => 'default',
    'multiple' => false,
    'mimeType' => null,
])

{{--
    The picker modal on its own, with nothing visible.

    For a caller that already has its own way to open it — the blog editor's
    toolbar button calls Flux.modal('media-picker-rich-text-image').show() and
    needs only something to open.

    A separate view rather than a flag on picker-field, because the packages
    that include it do not depend on this one: with a flag, an older copy of
    this package silently drew a Choose card in the middle of a page, and there
    was no version constraint able to prevent it. A view that does not exist is
    simply not included, so the worst case is a button that does nothing.

        // config/blog.php
        'media_picker_modal_view' => 'media::picker-modal',

    Picking dispatches `media-picked` with ids, group and items, exactly as the
    full field does.
--}}
@php
    $pickerParams = ['group' => $group, 'multiple' => $multiple];

    if ($mimeType) {
        $pickerParams['mimeType'] = $mimeType;
    }
@endphp

<flux:modal name="media-picker-{{ $group }}" class="max-w-6xl">
    @livewire('media.picker', $pickerParams, key('picker-'.$group))
</flux:modal>
