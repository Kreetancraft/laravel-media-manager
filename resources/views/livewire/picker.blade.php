<div class="flex flex-col gap-4">
    {{-- Header: title + selected count --}}
    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ __('Select Media') }}</flux:heading>
        <flux:badge size="sm" color="zinc">{{ count($selected) }} {{ __('selected') }}</flux:badge>
    </div>

    <div class="flex flex-col gap-4 md:flex-row">
        {{-- Left rail: folder tree (WordPress "All media" + folders) --}}
        <aside class="w-full shrink-0 md:w-56">
            <nav class="space-y-0.5">
                <button
                    type="button"
                    wire:click="openFolder(null)"
                    @class([
                        'flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-sm transition',
                        'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-700 dark:text-zinc-100' => $currentFolderId === null,
                        'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700' => $currentFolderId !== null,
                    ])
                >
                    <flux:icon name="photo" variant="micro" />
                    {{ __('All media') }}
                </button>

                @foreach ($allFolders as $node)
                    @php($folder = $node['folder'])
                    <button
                        type="button"
                        wire:click="openFolder({{ $folder->id }})"
                        style="padding-left: {{ 0.625 + $node['depth'] * 0.85 }}rem"
                        @class([
                            'flex w-full items-center gap-1.5 rounded-md px-2.5 py-1.5 text-left text-sm transition',
                            'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-700 dark:text-zinc-100' => $currentFolderId === $folder->id,
                            'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700' => $currentFolderId !== $folder->id,
                        ])
                    >
                        <flux:icon name="folder" variant="micro" class="size-4 text-amber-400" />
                        <span class="truncate">{{ $folder->name }}</span>
                    </button>
                @endforeach
            </nav>
        </aside>

        {{-- Center: breadcrumb + filters + upload + grid --}}
        <section class="min-w-0 flex-1">
            {{-- Breadcrumb / up --}}
            <div class="mb-3 flex flex-wrap items-center gap-1.5 text-sm">
                @if ($current && $current->parent_id !== null)
                    <flux:button size="xs" variant="ghost" icon="chevron-left" wire:click="goUp" class="px-1.5!" />
                @endif
                <button type="button" wire:click="openFolder(null)" class="flex items-center rounded px-1.5 py-0.5 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700">
                    <flux:icon name="home" variant="micro" />
                </button>
                @foreach ($ancestors as $crumb)
                    @if (! $loop->first)
                        <span class="text-zinc-300 dark:text-zinc-600">/</span>
                    @endif
                    @if ($loop->last)
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $crumb->name }}</span>
                    @else
                        <button type="button" wire:click="openFolder({{ $crumb->id }})" class="rounded px-1.5 py-0.5 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700">{{ $crumb->name }}</button>
                    @endif
                @endforeach

                <label class="ml-auto flex items-center gap-1.5 text-xs text-zinc-500">
                    <input type="checkbox" wire:model.live="includeSubfolders" class="rounded border-zinc-300 dark:border-zinc-600">
                    {{ __('Include subfolders') }}
                </label>
            </div>

            {{-- Filter tabs + search + date --}}
            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="flex items-center gap-1 text-sm">
                    <button type="button" wire:click="$set('filterType', '')" @class(['rounded-md px-2 py-1', 'bg-zinc-100 font-medium dark:bg-zinc-700' => $filterType === '', 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700' => $filterType !== ''])">{{ __('All') }}</button>
                    <button type="button" wire:click="$set('filterType', 'image')" @class(['rounded-md px-2 py-1', 'bg-zinc-100 font-medium dark:bg-zinc-700' => $filterType === 'image', 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700' => $filterType !== 'image'])>{{ __('Images') }}</button>
                    <button type="button" wire:click="$set('filterType', 'document')" @class(['rounded-md px-2 py-1', 'bg-zinc-100 font-medium dark:bg-zinc-700' => $filterType === 'document', 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700' => $filterType !== 'document'])>{{ __('Documents') }}</button>
                </div>

                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('Search…') }}"
                    class="w-full sm:w-48"
                    size="sm"
                />

                <select wire:model.live="filterDate" class="rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                    <option value="">{{ __('All dates') }}</option>
                    @foreach ($uploadedDates as $ym)
                        <option value="{{ $ym }}">{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('F Y') }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Upload drop-zone --}}
            <div
                x-data="{ drag: false }"
                x-on:dragover.prevent="drag = true"
                x-on:dragleave.prevent="drag = false"
                x-on:drop.prevent="drag = false; $refs.pickerFile.click()"
                @class([
                    'mb-3 rounded-lg border-2 border-dashed p-3 text-center transition border-zinc-300 text-zinc-500 dark:border-zinc-600 dark:text-zinc-400',
                ])
            >
                <flux:icon name="arrow-up-tray" variant="micro" class="mx-auto mb-1 size-5" />
                <flux:text class="text-xs">{{ __('Drop files here or') }}</flux:text>
                <label class="cursor-pointer text-xs font-medium text-accent-content hover:underline text-accent-content">
                    {{ __('browse to upload') }}
                    <input type="file" multiple class="hidden" x-ref="pickerFile" wire:model="uploads" accept="image/*,application/pdf">
                </label>
            </div>

            @if ($search !== '')
                <flux:text class="mb-2 text-xs text-zinc-500">{{ __('Searching across all folders. Clear search to browse.') }}</flux:text>
            @endif

            {{-- Grid --}}
            <div class="grid max-h-[48vh] grid-cols-3 gap-2 overflow-y-auto p-1 sm:grid-cols-4 md:grid-cols-5">
                {{-- Folder tiles --}}
                @foreach ($folders as $folder)
                    <button
                        type="button"
                        wire:key="folder-{{ $folder->id }}"
                        wire:click="openFolder({{ $folder->id }})"
                        class="group flex aspect-square flex-col items-center justify-center gap-1 rounded-lg border border-zinc-200 bg-zinc-50 px-2 text-center transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700"
                    >
                        <flux:icon name="folder" class="size-7 text-amber-400" />
                        <span class="line-clamp-2 text-xs font-medium text-zinc-700 dark:text-zinc-200">{{ $folder->name }}</span>
                        <span class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $folder->elements() }}</span>
                    </button>
                @endforeach

                {{-- Media tiles --}}
                @forelse ($items as $item)
                    <button
                        type="button"
                        wire:key="picker-{{ $item['id'] }}"
                        wire:click="toggle({{ $item['id'] }})"
                        @class([
                            'group relative aspect-square overflow-hidden rounded-lg border-2 transition focus:outline-none',
                            'border-accent ring-2 ring-accent/30' => $this->isSelected($item['id']),
                            'border-transparent hover:border-zinc-300 dark:hover:border-zinc-600' => ! $this->isSelected($item['id']),
                        ])
                    >
                        @if ($item['is_image'] && $item['thumb'])
                            <img src="{{ $item['thumb'] }}" alt="{{ $item['alt'] }}" loading="lazy" decoding="async" class="size-full object-cover" />
                        @else
                            @php($isPdf = str_contains((string) $item['mime'], 'pdf'))
                            <div class="flex size-full flex-col items-center justify-center gap-1 p-2 text-center {{ $isPdf ? 'bg-rose-50 dark:bg-rose-900/20' : 'bg-zinc-100 dark:bg-zinc-800' }}">
                                <flux:icon name="document" class="size-6 {{ $isPdf ? 'text-rose-500' : 'text-zinc-500' }}" />
                                <span class="line-clamp-2 text-[10px] text-zinc-600 dark:text-zinc-300">{{ $item['name'] }}</span>
                            </div>
                        @endif
                        @if ($this->isSelected($item['id']))
                            <span class="absolute top-1 right-1 flex size-5 items-center justify-center rounded-full bg-accent text-white shadow">
                                <flux:icon name="check" variant="micro" />
                            </span>
                        @endif
                    </button>
                @empty
                    <div class="col-span-full flex flex-col items-center gap-2 py-16 text-center">
                        <flux:icon name="photo" class="size-9 text-zinc-300 dark:text-zinc-600" />
                        <flux:text class="text-sm text-zinc-500">
                            {{ $search !== '' ? __('No matches found.') : ($folders->isEmpty() ? __('This folder is empty.') : __('No media in this folder.')) }}
                        </flux:text>
                    </div>
                @endforelse
            </div>

            @if (count($selected) > 0)
                <div class="mt-2 flex items-center gap-2">
                    <flux:button size="xs" variant="ghost" wire:click="selectAllOnPage">{{ __('Select all on page') }}</flux:button>
                    <flux:button size="xs" variant="ghost" wire:click="clear">{{ __('Clear selection') }}</flux:button>
                </div>
            @endif

            @if ($items->hasPages())
                <div class="mt-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">{{ $items->links() }}</div>
            @endif
        </section>

        {{-- Right rail: attachment details (WordPress-style) --}}
        <aside class="w-full shrink-0 md:w-64">
            @if ($selectedMedia)
                <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:heading size="sm">{{ __('Attachment details') }}</flux:heading>


                    @if ($selectedMedia['is_image'] && $selectedMedia['thumb'])
                        <img src="{{ $selectedMedia['thumb'] }}" alt="{{ $selectedMedia['alt'] }}" class="w-full rounded-md object-cover">
                    @else
                        <div class="flex h-32 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="document" class="size-8 text-zinc-400" />
                        </div>
                    @endif

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">{{ __('File name') }}</span>
                            <span class="truncate pl-2 font-medium">{{ $selectedMedia['file_name'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">{{ __('Type') }}</span>
                            <span>{{ $selectedMedia['mime'] }}</span>
                        </div>
                        @if ($selectedMedia['width'] && $selectedMedia['height'])
                            <div class="flex justify-between">
                                <span class="text-zinc-500">{{ __('Dimensions') }}</span>
                                <span>{{ $selectedMedia['width'] }} × {{ $selectedMedia['height'] }}px</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-zinc-500">{{ __('Size') }}</span>
                            <span>{{ number_format($selectedMedia['size'] / 1024, 1) }} KB</span>
                        </div>
                    </div>

                    <flux:input
                        wire:model.blur="selectedMedia.name"
                        wire:change="saveDetail({{ $selectedMedia['id'] }}, 'title', $event.target.value)"
                        label="{{ __('Title') }}"
                        size="sm"
                    />
                    <flux:input
                        wire:model.blur="selectedMedia.alt"
                        wire:change="saveDetail({{ $selectedMedia['id'] }}, 'alt_text', $event.target.value)"
                        label="{{ __('Alt text') }}"
                        size="sm"
                    />
                    <flux:textarea
                        wire:model.blur="selectedMedia.caption"
                        wire:change="saveDetail({{ $selectedMedia['id'] }}, 'caption', $event.target.value)"
                        label="{{ __('Caption') }}"
                        size="sm"
                        rows="2"
                    />
                    <flux:textarea
                        wire:model.blur="selectedMedia.description"
                        wire:change="saveDetail({{ $selectedMedia['id'] }}, 'description', $event.target.value)"
                        label="{{ __('Description') }}"
                        size="sm"
                        rows="2"
                    />

                    <div class="space-y-1">
                        <flux:label class="text-xs">{{ __('URL') }}</flux:label>
                        <flux:input :value="$selectedMedia['url']" readonly size="sm" />
                    </div>
                </div>
            @else
                <div class="flex h-full flex-col items-center justify-center rounded-lg border border-dashed border-zinc-200 p-6 text-center text-sm text-zinc-400 dark:border-zinc-700">
                    <flux:icon name="information-circle" variant="micro" class="mb-2 size-6" />
                    {{ __('Select an item to see its details.') }}
                </div>
            @endif
        </aside>
    </div>

    {{-- Actions --}}
    <div class="flex justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
        <flux:modal.close>
            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
        </flux:modal.close>
        <flux:button variant="primary" wire:click="confirm" :disabled="count($selected) === 0">
            {{ __('Use selected') }}
        </flux:button>
    </div>
</div>
