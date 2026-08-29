@props([
    'items' => [],
    'group' => 'default',
    'multiple' => false,
    'label' => null,
    'emptyLabel' => null,
    'icon' => 'photo',
    'mimeType' => null,
])

{{--
    A ready-made image field: the tiles that are chosen, a Choose button, and the
    picker modal behind it.

    This exists so packages that ship no image handling do not each have to ask
    you to write one. kreetancraft/laravel-blog and kreetancraft/laravel-seo call
    whatever view their config names, handing it $items, $group and $multiple —
    point them here and you are done:

        // config/blog.php
        'media_picker_view' => 'media::picker-field',

        // config/seo.php
        'og_picker_view' => 'media::picker-field',

    $items are already resolved by MediaImageResolver, so tiles appear the moment
    something is picked rather than after a save. Choosing dispatches
    `media-picked` with ids, group and items — the shape those packages listen
    for. There is no remove button by design: picking again replaces the
    selection, which is one interaction instead of two.
--}}
@php
    $pickerParams = ['group' => $group, 'multiple' => $multiple];

    if ($mimeType) {
        $pickerParams['mimeType'] = $mimeType;
    }
@endphp

<div class="space-y-2 rounded-xl border border-zinc-200 bg-zinc-50/40 p-4 transition-all duration-200 focus-within:border-zinc-400 dark:border-zinc-800/60 dark:bg-zinc-900/20 dark:focus-within:border-zinc-700">
    @if ($label)
        <div class="flex items-center justify-between">
            <flux:text class="font-medium">{{ $label }}</flux:text>
            @if (! empty($items))
                <flux:badge size="sm" color="zinc">{{ count($items) }}</flux:badge>
            @endif
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        @forelse ($items as $item)
            @php($url = $item['url'] ?? null)
            <div class="relative" wire:key="{{ $group }}-{{ $item['id'] }}">
                @if ($url && (str_ends_with(strtolower($url), '.pdf') || str_contains($url, '.pdf?')))
                    <a
                        href="{{ $url }}"
                        target="_blank"
                        rel="noopener"
                        class="flex size-16 items-center justify-center rounded-lg bg-rose-50 ring-1 ring-zinc-200 dark:bg-rose-900/20 dark:ring-zinc-700"
                        title="{{ $item['name'] ?? '' }}"
                    >
                        <flux:icon name="document" class="size-6 text-rose-500" />
                    </a>
                @else
                    <img
                        src="{{ $url }}"
                        alt="{{ $item['name'] ?? '' }}"
                        class="size-16 rounded-lg object-cover ring-1 ring-zinc-200 dark:ring-zinc-700"
                    />
                @endif
            </div>
        @empty
            <div class="flex size-16 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="{{ $icon }}" class="size-5 text-zinc-400" />
            </div>
        @endforelse

        <flux:modal.trigger name="media-picker-{{ $group }}">
            <flux:button size="sm" icon="photo" variant="filled">{{ __('Choose') }}</flux:button>
        </flux:modal.trigger>
    </div>

    @if (empty($items) && $emptyLabel)
        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ $emptyLabel }}</flux:text>
    @endif

    {{-- @livewire() rather than the :bindings shorthand: the shorthand does not
         resolve inside a nested Blade component, and the params have to be
         evaluated in this scope. --}}
    <flux:modal name="media-picker-{{ $group }}" class="max-w-6xl">
        @livewire('media.picker', $pickerParams, key('picker-'.$group))
    </flux:modal>
</div>
