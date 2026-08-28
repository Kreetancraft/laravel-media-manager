@php
    $file = $file ?? [];
    $isImage = $file['isImage'] ?? false;
    $mime = $file['mime_type'] ?? '';
    $responsive = ['thumbnail', 'medium', 'large'];
@endphp

<div class="flex flex-col md:flex-row w-[95vw] md:w-[72vw] rounded-xl overflow-hidden shadow-xl ring-1 ring-black/10 dark:ring-white/10"
     style="height: 85vh;">

    {{-- LEFT: Image panel (theme-aware bg) — the hero --}}
    <div class="relative bg-zinc-100 dark:bg-zinc-950 flex flex-col min-w-0 h-[220px] md:h-auto md:flex-1">

        {{-- Prev / Next arrows --}}
        <button wire:click="navigateToAdjacentMedia('prev')"
                class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-zinc-800/70 hover:bg-zinc-700 text-white flex items-center justify-center transition shadow-sm"
                title="{{ __('Previous') }}">
            <flux:icon name="chevron-left" class="w-5 h-5" />
        </button>
        <button wire:click="navigateToAdjacentMedia('next')"
                class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 rounded-full bg-zinc-800/70 hover:bg-zinc-700 text-white flex items-center justify-center transition shadow-sm"
                title="{{ __('Next') }}">
            <flux:icon name="chevron-right" class="w-5 h-5" />
        </button>

        {{-- Image / File icon centred --}}
        <div class="flex-1 flex items-center justify-center p-6 overflow-hidden">
            @if($isImage && ($file['displayUrl'] ?? null))
                <img src="{{ $file['displayUrl'] }}"
                     alt="{{ $file['name'] ?? '' }}"
                     class="max-w-full max-h-full object-contain rounded-lg shadow-lg" />
            @elseif($mime === 'application/pdf')
                <div class="flex flex-col items-center gap-2">
                    <flux:icon name="document-text" class="w-24 h-24 text-red-400" />
                    <span class="text-xs font-semibold text-red-400 uppercase tracking-widest">PDF Document</span>
                </div>
            @elseif(str_contains($mime, 'zip') || str_contains($mime, 'rar'))
                <div class="flex flex-col items-center gap-2">
                    <flux:icon name="archive-box" class="w-24 h-24 text-amber-400" />
                    <span class="text-xs font-semibold text-amber-400 uppercase tracking-widest">Archive</span>
                </div>
            @else
                <div class="flex flex-col items-center gap-2">
                    <flux:icon name="document" class="w-24 h-24 text-zinc-400" />
                    <span class="text-xs font-semibold text-zinc-400 uppercase tracking-widest">File</span>
                </div>
            @endif
        </div>
    </div>

    {{-- RIGHT: Details panel --}}
    <div class="flex-1 md:flex-none md:w-[320px] shrink-0 flex flex-col bg-white dark:bg-zinc-900 min-h-0">

        {{-- Header: eyebrow + filename (protagonist) + close --}}
        <div class="flex items-start justify-between gap-3 px-4 pt-4 pb-3 border-b border-zinc-200 dark:border-zinc-800 shrink-0">
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Attachment Details') }}</p>
                <h2 class="mt-0.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">{{ $file['name'] ?? '' }}</h2>
            </div>
            <flux:modal.close>
                <button class="shrink-0 w-7 h-7 rounded-md text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center justify-center transition">
                    <flux:icon name="x-mark" class="w-4 h-4" />
                </button>
            </flux:modal.close>
        </div>

        {{-- Compact meta line --}}
        <div class="px-4 py-2.5 border-b border-zinc-100 dark:border-zinc-800 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
            <span>{{ $file['mime_type'] ?? '' }}</span>
            @if(($file['human_readable_size'] ?? null))
                <span class="text-zinc-300 dark:text-zinc-600">&bull;</span>
                <span>{{ $file['human_readable_size'] }}</span>
            @endif
            @if(($file['created_at'] ?? null))
                <span class="text-zinc-300 dark:text-zinc-600">&bull;</span>
                <span>{{ $file['created_at'] }}</span>
            @endif
            @if(($file['author'] ?? null))
                <span class="text-zinc-300 dark:text-zinc-600">&bull;</span>
                <span>{{ __('By') }} {{ $file['author'] }}</span>
            @endif
        </div>

        {{-- Scrollable form fields --}}
        <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3">

            {{-- Alt Text --}}
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Alt Text') }}</label>
                    <button type="button"
                            wire:click="generateAltText"
                            class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition">
                        <flux:icon name="sparkles" class="w-3.5 h-3.5" />
                        {{ __('Generate') }}
                    </button>
                </div>
                <flux:textarea wire:model.live.debounce.500ms="metadata.alt_text" rows="2" class="text-xs" placeholder="{{ __('Describe the image for screen readers...') }}" />
            </div>

            {{-- Title --}}
            <div class="space-y-1">
                <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Title') }}</label>
                <flux:input wire:model.live.debounce.500ms="metadata.title" class="text-xs" />
            </div>

            {{-- Caption --}}
            <div class="space-y-1">
                <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Caption') }}</label>
                <flux:textarea wire:model.live.debounce.500ms="metadata.caption" rows="2" class="text-xs" />
            </div>

            {{-- Description --}}
            <div class="space-y-1">
                <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Description') }}</label>
                <flux:textarea wire:model.live.debounce.500ms="metadata.description" rows="3" class="text-xs" />
            </div>

            {{-- Copy URL / Markdown --}}
            <div class="space-y-1">
                <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Copy') }}</label>
                <div class="flex flex-wrap gap-1.5">
                    <flux:button size="sm" icon="clipboard" wire:click="copyUrl">{{ __('URL') }}</flux:button>
                    <flux:button size="sm" icon="clipboard-document" wire:click="copyMarkdown">{{ __('Markdown') }}</flux:button>
                    @foreach($responsive as $size)
                        <flux:button size="sm" variant="ghost" wire:click="copyUrl('{{ $size }}')">{{ ucfirst($size) }}</flux:button>
                    @endforeach
                </div>
            </div>

            {{-- Used in --}}
            <div class="space-y-1">
                <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Used in') }}</label>
                @if(count($usage) === 0)
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 italic">{{ __('Not attached to any content yet.') }}</p>
                @else
                    <ul class="space-y-1">
                        @foreach($usage as $item)
                            <li class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800/60 rounded-md px-2 py-1.5">
                                <flux:icon name="paper-clip" class="w-3.5 h-3.5 text-zinc-400 shrink-0" />
                                <span class="font-medium">{{ $item['type'] }}:</span>
                                <span class="truncate">{{ $item['title'] }}</span>
                                @if($item['collection'] !== 'default')
                                    <span class="text-zinc-400 dark:text-zinc-500">&bull; {{ $item['collection'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>

        {{-- Sticky footer --}}
        <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 shrink-0 flex items-center justify-between gap-3">
            <flux:button
                variant="danger"
                icon="trash"
                size="sm"
                title="{{ __('Delete Permanently') }}"
                wire:click="deleteCurrent"
            />
            @if($isImage)
                <a href="{{ $file['id'] ?? 0 ? url('admin/media').'/'.($file['id'] ?? 0).'/edit' : '#' }}"
                   title="{{ __('Edit Image') }}"
                   class="inline-flex items-center justify-center rounded-md border border-zinc-200 dark:border-zinc-700 w-8 h-8 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <flux:icon name="pencil" class="w-4 h-4" />
                </a>
            @endif
        </div>

    </div>
</div>
