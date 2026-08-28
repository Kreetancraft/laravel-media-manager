<div class="p-6 space-y-5 relative"
    x-data="{
        selectedFolders: @entangle('selectedFolders'),
        selectedFiles: @entangle('selectedFiles'),
        isDragging: false,
        folderIdToDelete: null,
        fileIdToDelete: null,
        
        toggleFolder(id) {
            if (this.selectedFolders.includes(id)) {
                this.selectedFolders = this.selectedFolders.filter(fId => fId !== id);
            } else {
                this.selectedFolders.push(id);
            }
        },
        toggleFile(id) {
            if (this.selectedFiles.includes(id)) {
                this.selectedFiles = this.selectedFiles.filter(fId => fId !== id);
            } else {
                this.selectedFiles.push(id);
            }
        },
        isFolderSelected(id) {
            return this.selectedFolders.includes(id);
        },
        isFileSelected(id) {
            return this.selectedFiles.includes(id);
        },
         confirmDeleteFolder(id) {
             this.folderIdToDelete = id;
             Flux.modal('confirm-delete-folder-modal').show();
         },
         confirmDeleteFile(id) {
             this.fileIdToDelete = id;
             Flux.modal('confirm-delete-file-modal').show();
         },
         uploading: false,
          uploadProgress: 0,
          handleDrop(event) {
              Flux.modal('upload-media-modal').show();
          },
      }"
     x-on:dragover.prevent="isDragging = true"
     x-on:dragleave.prevent="isDragging = false"
     x-on:drop.prevent="isDragging = false; handleDrop($event)"
     x-on:livewire-upload-start="uploading = true"
     x-on:livewire-upload-finish="uploading = false"
     x-on:livewire-upload-error="uploading = false"
      x-on:livewire-upload-progress="uploadProgress = $event.detail.progress"
>

    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ config('media.routes.names.home', '/') }}" wire:navigate>{{ __('Dashboard') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Media') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Drag & Drop Upload Overlay -->
    <div x-show="isDragging"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute inset-0 z-40 bg-zinc-950/70 backdrop-blur-sm m-6 rounded-2xl border-4 border-dashed border-primary-500 flex flex-col items-center justify-center text-white pointer-events-none transition duration-200"
         x-cloak>
        <flux:icon name="arrow-up-tray" class="w-16 h-16 text-primary-400 animate-bounce mb-4" />
        <flux:heading size="xl" class="text-white">{{ __('Drop files to upload') }}</flux:heading>
        <flux:subheading class="text-zinc-300 mt-2">{{ __('Upload files directly to the current folder.') }}</flux:subheading>
    </div>

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div>
                <flux:heading size="xl" class="inline-block">{{ __('Media Gallery') }}</flux:heading>
                <flux:subheading>{{ __('Manage uploads, folders, and assets.') }}</flux:subheading>
            </div>
        </div>

        <!-- Actions Toolbar -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Create Folder Modal Trigger -->
            <flux:modal.trigger name="create-folder-modal">
                <flux:button icon="folder-plus" dusk="create-folder-btn">{{ __('New Folder') }}</flux:button>
            </flux:modal.trigger>

            <!-- Upload Files — opens the drag & drop / browse popup -->
            <flux:modal.trigger name="upload-media-modal">
                <flux:button variant="primary" icon="arrow-up-tray">
                    {{ __('Upload Files') }}
                </flux:button>
            </flux:modal.trigger>

            <!-- Upload Progress Toast -->
            <div x-show="uploading"
                 class="fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-zinc-900/90 dark:bg-zinc-950/95 text-white p-4 rounded-xl shadow-xl border border-zinc-800 backdrop-blur-md transition-all duration-300"
                 x-cloak>
                <svg class="animate-spin h-5 w-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-semibold tracking-wide flex items-center gap-1.5">
                    <span>Uploading:</span>
                    <span class="text-primary-400" x-text="uploadProgress + '%'"></span>
                </span>
            </div>
        </div>
    </div>

    <flux:separator />

    <!-- Filters Toolbar (WordPress Style) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-zinc-50 dark:bg-zinc-900/40 p-3 rounded-xl border border-zinc-200/60 dark:border-zinc-800/80">
        <div class="flex flex-wrap items-center gap-3">
            <!-- View Mode Toggles -->
            <div class="flex items-center border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden bg-white dark:bg-zinc-800 h-9 shrink-0 shadow-sm">
                <button wire:click="$set('viewMode', 'grid')" 
                        class="px-2.5 h-full transition flex items-center justify-center hover:bg-zinc-50 dark:hover:bg-zinc-700 {{ $viewMode === 'grid' ? 'bg-zinc-100 dark:bg-zinc-700 text-primary-600 dark:text-primary-400 font-semibold' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
                        title="{{ __('Grid View') }}">
                    <flux:icon name="squares-2x2" class="w-4 h-4" />
                </button>
                <button wire:click="$set('viewMode', 'list')" 
                        class="px-2.5 h-full transition border-l border-zinc-200 dark:border-zinc-700 flex items-center justify-center hover:bg-zinc-50 dark:hover:bg-zinc-700 {{ $viewMode === 'list' ? 'bg-zinc-100 dark:bg-zinc-700 text-primary-600 dark:text-primary-400 font-semibold' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
                        title="{{ __('List View') }}">
                    <flux:icon name="list-bullet" class="w-4 h-4" />
                </button>
            </div>

            <!-- Type Filter -->
            <flux:select wire:model.live="filterType" class="w-40 sm:w-44 shadow-sm" size="sm">
                <option value="all">{{ __('All media items') }}</option>
                <option value="images">{{ __('Images') }}</option>
                <option value="documents">{{ __('Documents') }}</option>
                <option value="archives">{{ __('Archives') }}</option>
            </flux:select>

            <!-- Date Filter -->
            <flux:select wire:model.live="filterDate" class="w-40 sm:w-44 shadow-sm" size="sm">
                <option value="all">{{ __('All dates') }}</option>
                @foreach($uploadedDates as $date)
                    <option value="{{ $date['value'] }}">{{ $date['label'] }}</option>
                @endforeach
            </flux:select>
        </div>

        <!-- Search -->
        <div class="w-full md:w-auto">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search media...') }}" 
                icon="magnifying-glass"
                class="w-full md:w-64"
                size="sm"
            />
        </div>
    </div>

    <!-- Breadcrumbs / Selection Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-1 border-b border-zinc-100 dark:border-zinc-800 pb-3">
        <!-- Breadcrumbs -->
        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 overflow-x-auto">
            @foreach($breadcrumb as $index => $crumb)
                @if($index > 0)
                    <flux:icon name="chevron-right" class="w-4 h-4 text-zinc-300 dark:text-zinc-600 shrink-0" />
                @endif
                @if($loop->last)
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200 shrink-0">
                        {{ $crumb['name'] }}
                    </span>
                @else
                    <button wire:click="navigateToFolder({{ $crumb['id'] }})" class="hover:text-primary-600 font-medium transition shrink-0">
                        {{ $crumb['name'] }}
                    </button>
                @endif
            @endforeach
        </div>

        <!-- Selection Controls / Bulk Actions -->
        <div class="flex items-center gap-2">
            <!-- Selected Actions -->
            <div x-show="selectedFolders.length > 0 || selectedFiles.length > 0" class="flex items-center gap-2" x-cloak>
                <span class="text-xs font-semibold bg-primary-50 dark:bg-primary-950/20 text-primary-600 dark:text-primary-400 px-2.5 py-1 rounded-full border border-primary-100 dark:border-primary-900/30">
                    <span x-text="(selectedFolders.length + selectedFiles.length) + ' ' + (selectedFolders.length + selectedFiles.length === 1 ? 'item' : 'items') + ' selected'"></span>
                </span>

                <flux:button size="sm" variant="ghost" x-on:click="selectedFolders = []; selectedFiles = [];">
                    {{ __('Clear') }}
                </flux:button>

                <flux:modal.trigger name="move-items-modal">
                    <flux:button size="sm" icon="arrows-up-down">{{ __('Move') }}</flux:button>
                </flux:modal.trigger>

                <flux:button size="sm" variant="danger" icon="trash" x-on:click="Flux.modal('confirm-delete-selected-modal').show()">
                    {{ __('Delete') }}
                </flux:button>
            </div>

            <!-- Select All (when nothing selected) -->
            <div x-show="selectedFolders.length === 0 && selectedFiles.length === 0">
                <flux:button size="sm" variant="ghost" x-on:click="$wire.selectAll()">
                    {{ __('Select All') }}
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Main Grid / Contents -->
    @if($subfolders->isEmpty() && $files->isEmpty())
        <!-- EMPTY STATE -->
        <div class="flex flex-col items-center justify-center border-2 border-dashed border-zinc-250 dark:border-zinc-800 rounded-2xl p-12 text-center bg-zinc-50/50 dark:bg-zinc-900/10">
            <div class="p-4 bg-zinc-100 dark:bg-zinc-800 rounded-2xl text-zinc-400 dark:text-zinc-500 mb-4 shadow-sm">
                <flux:icon name="folder" class="w-12 h-12" />
            </div>
            <flux:heading size="lg" class="mb-1">{{ __('No files or folders found') }}</flux:heading>
            <flux:subheading class="max-w-md mb-6">
                {{ __('This directory is currently empty. Upload files or create a subfolder to get started.') }}
            </flux:subheading>
            <div class="flex items-center gap-3">
                <flux:modal.trigger name="create-folder-modal">
                    <flux:button icon="folder-plus">{{ __('New Folder') }}</flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="upload-media-modal">
                    <flux:button variant="primary" icon="arrow-up-tray">
                        {{ __('Upload Files') }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    @else
        <!-- Subfolders Grid -->
        @if($subfolders->isNotEmpty())
            <div class="space-y-3">
                <flux:heading size="md">{{ __('Folders') }}</flux:heading>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($subfolders as $folder)
                        <!-- Folder Card (Draggable and Drop Target) -->
                        <div draggable="true"
                             x-on:dragstart="event.dataTransfer.setData('text/plain', JSON.stringify({type: 'folder', id: {{ $folder['id'] }}}))"
                             x-on:dragover.prevent
                             x-on:drop="const data = JSON.parse(event.dataTransfer.getData('text/plain')); $wire.moveItem(data.id, data.type, {{ $folder['id'] }})"
                             class="group relative bg-white dark:bg-zinc-800/50 hover:bg-zinc-50 dark:hover:bg-zinc-800/80 border rounded-xl p-4 flex flex-col justify-between cursor-pointer transition shadow-sm border-zinc-200 dark:border-zinc-800/80"
                             :class="isFolderSelected({{ $folder['id'] }}) ? 'ring-2 ring-primary-500 border-primary-500 bg-primary-50/5 dark:bg-primary-950/5' : ''"
                             wire:click="navigateToFolder({{ $folder['id'] }})">
                            
                            <!-- Selection Checkbox & Folder Icon -->
                            <div class="flex items-start justify-between mb-3">
                                <!-- Checkbox Toggle -->
                                <button x-on:click.prevent.stop="toggleFolder({{ $folder['id'] }})" class="p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition">
                                    <flux:icon x-show="isFolderSelected({{ $folder['id'] }})" name="check-circle" class="w-5 h-5 text-primary-500 fill-current" />
                                    <div x-show="!isFolderSelected({{ $folder['id'] }})" class="w-5 h-5 border-2 border-zinc-300 dark:border-zinc-600 rounded-full group-hover:border-zinc-400"></div>
                                </button>

                                <span class="text-amber-500">
                                    <flux:icon name="folder" class="w-8 h-8 fill-current" />
                                </span>

                                <!-- Actions Dropdown -->
                                <div wire:click.stop>
                                    <flux:dropdown align="end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" dusk="folder-dropdown-{{ $folder['id'] }}" />
                                        <flux:menu class="min-w-[120px]">
                                            <flux:menu.item wire:click="startFolderRename({{ $folder['id'] }})" icon="pencil" dusk="rename-folder-{{ $folder['id'] }}">{{ __('Rename') }}</flux:menu.item>
                                            <flux:menu.item x-on:click="confirmDeleteFolder({{ $folder['id'] }})" variant="danger" icon="trash" dusk="delete-folder-{{ $folder['id'] }}">{{ __('Delete') }}</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>

                            <!-- Title -->
                            <div class="truncate">
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 text-sm block truncate" title="{{ $folder['name'] }}">
                                    {{ $folder['name'] }}
                                </span>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ $folder['elements'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Files Gallery -->
        @if($files->isNotEmpty())
            @if($viewMode === 'list')
                <!-- List View Table -->
                <div class="border border-zinc-200 dark:border-zinc-800/80 rounded-2xl overflow-hidden bg-white dark:bg-zinc-900 shadow-sm mt-4">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column class="w-12"></flux:table.column>
                            <flux:table.column>{{ __('File') }}</flux:table.column>
                            <flux:table.column>{{ __('Author') }}</flux:table.column>
                            <flux:table.column>{{ __('Date') }}</flux:table.column>
                            <flux:table.column>{{ __('File Type') }}</flux:table.column>
                            <flux:table.column>{{ __('File Size') }}</flux:table.column>
                            <flux:table.column class="w-12"></flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach($files as $file)
                                <flux:table.row :key="$file['id']"
                                                class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-850/50"
                                                x-on:click="$wire.openDetails({{ $file['id'] }})">
                                    <!-- Checkbox Column -->
                                    <flux:table.cell x-on:click.stop>
                                        <button x-on:click.prevent.stop="toggleFile({{ $file['id'] }})" class="p-1 rounded bg-white dark:bg-zinc-800 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition">
                                            <flux:icon x-show="isFileSelected({{ $file['id'] }})" name="check-circle" class="w-5 h-5 text-primary-500 fill-current" />
                                            <div x-show="!isFileSelected({{ $file['id'] }})" class="w-5 h-5 border-2 border-zinc-300 dark:border-zinc-600 rounded-full"></div>
                                        </button>
                                    </flux:table.cell>

                                    <!-- Thumbnail & File Name -->
                                    <flux:table.cell class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-950 flex items-center justify-center shrink-0 border border-zinc-200/50 dark:border-zinc-800">
                                            @if($file['isImage'])
                                                <img src="{{ $file['thumbnailUrl'] }}" alt="{{ $file['name'] }}" class="object-cover w-full h-full" />
                                            @else
                                                @if($file['mime_type'] === 'application/pdf')
                                                    <flux:icon name="document-text" class="w-6 h-6 text-red-500" />
                                                @elseif(str_contains($file['mime_type'], 'zip') || str_contains($file['mime_type'], 'rar'))
                                                    <flux:icon name="archive-box" class="w-6 h-6 text-amber-500" />
                                                @else
                                                    <flux:icon name="document" class="w-6 h-6 text-zinc-400" />
                                                @endif
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200 text-sm block truncate">{{ $file['name'] }}</span>
                                            <span class="text-zinc-400 dark:text-zinc-500 text-xs block truncate">{{ $file['file_name'] }}</span>
                                        </div>
                                    </flux:table.cell>

                                    <!-- Author -->
                                    <flux:table.cell>
                                        <span class="text-zinc-700 dark:text-zinc-300 text-sm">
                                            {{ $file['author'] }}
                                        </span>
                                    </flux:table.cell>

                                    <!-- Date -->
                                    <flux:table.cell>
                                        <span class="text-zinc-500 dark:text-zinc-400 text-sm">
                                            {{ $file['created_at_date'] }}
                                        </span>
                                    </flux:table.cell>

                                    <!-- File Type -->
                                    <flux:table.cell>
                                        <span class="text-zinc-500 dark:text-zinc-400 text-xs uppercase bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded border border-zinc-200/50 dark:border-zinc-700">
                                            {{ explode('/', $file['mime_type'])[1] ?? $file['mime_type'] }}
                                        </span>
                                    </flux:table.cell>

                                    <!-- File Size -->
                                    <flux:table.cell>
                                        <span class="text-zinc-500 dark:text-zinc-400 text-sm">
                                            {{ $file['human_readable_size'] }}
                                        </span>
                                    </flux:table.cell>

                                    <!-- Actions Dropdown -->
                                    <flux:table.cell x-on:click.stop>
                                        <flux:dropdown align="end">
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" dusk="file-dropdown-{{ $file['id'] }}" />
                                            <flux:menu class="min-w-[120px]">
                                                <flux:menu.item wire:click="startFileRename({{ $file['id'] }})" icon="pencil" dusk="rename-file-{{ $file['id'] }}">{{ __('Rename') }}</flux:menu.item>
                                                <flux:menu.item x-on:click="confirmDeleteFile({{ $file['id'] }})" variant="danger" icon="trash" dusk="delete-file-{{ $file['id'] }}">{{ __('Delete') }}</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    <!-- Pagination Links -->
                    <div class="p-4 border-t border-zinc-200 dark:border-zinc-800/80">
                        {{ $files->links() }}
                    </div>
                </div>
            @else
                <!-- Grid View -->
                <div class="space-y-3 pt-4">
                    <flux:heading size="md">{{ __('Files') }}</flux:heading>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
                        @foreach($files as $file)
                            <!-- File Card (Draggable) -->
                            <div draggable="true"
                                 x-on:dragstart="event.dataTransfer.setData('text/plain', JSON.stringify({type: 'file', id: {{ $file['id'] }}}))"
                                 class="group relative bg-white dark:bg-zinc-800/50 border rounded-xl overflow-hidden cursor-pointer shadow-sm transition flex flex-col justify-between border-zinc-200 dark:border-zinc-800/80"
                                 :class="isFileSelected({{ $file['id'] }}) ? 'ring-2 ring-primary-500 border-primary-500 bg-primary-50/5 dark:bg-primary-950/5' : ''"
                                 x-on:click="$wire.openDetails({{ $file['id'] }})">
                                
                                <!-- Thumbnail / Icon Area -->
                                <div class="aspect-square w-full bg-zinc-50 dark:bg-zinc-950 flex items-center justify-center overflow-hidden border-b border-zinc-100 dark:border-zinc-800/60 relative">
                                    @if($file['isImage'])
                                        <img src="{{ $file['thumbnailUrl'] }}" alt="{{ $file['name'] }}" class="object-cover w-full h-full transition" loading="lazy" />
                                        <!-- Image WebP badge if WebP exists -->
                                        @if($file['webpUrl'])
                                            <span class="absolute top-2 left-2 bg-emerald-500/90 text-white text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm leading-none">
                                                WEBP
                                            </span>
                                        @endif
                                    @else
                                        <!-- Non-image file type icon -->
                                        <div class="text-zinc-400 dark:text-zinc-600 flex flex-col items-center gap-1">
                                            @if($file['mime_type'] === 'application/pdf')
                                                <flux:icon name="document-text" class="w-12 h-12 text-red-500" />
                                                <span class="text-[10px] font-bold text-red-500">PDF</span>
                                            @elseif(str_contains($file['mime_type'], 'zip') || str_contains($file['mime_type'], 'rar'))
                                                <flux:icon name="archive-box" class="w-12 h-12 text-amber-500" />
                                                <span class="text-[10px] font-bold text-amber-500">ZIP</span>
                                            @else
                                                <flux:icon name="document" class="w-12 h-12" />
                                                <span class="text-[10px] font-bold text-zinc-400 uppercase">FILE</span>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Checkbox Overlays -->
                                    <div class="absolute top-2 left-2 transition duration-200" :class="isFileSelected({{ $file['id'] }}) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'" x-on:click.stop>
                                        <button x-on:click.prevent.stop="toggleFile({{ $file['id'] }})" class="p-1 rounded bg-white/90 dark:bg-zinc-900/90 hover:bg-white dark:hover:bg-zinc-800 shadow-sm border border-zinc-200 dark:border-zinc-700 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition">
                                            <flux:icon x-show="isFileSelected({{ $file['id'] }})" name="check-circle" class="w-5 h-5 text-primary-500 fill-current" />
                                            <div x-show="!isFileSelected({{ $file['id'] }})" class="w-5 h-5 border-2 border-zinc-300 dark:border-zinc-600 rounded-full"></div>
                                        </button>
                                    </div>

                                    <!-- Quick Copy Link Button (on hover) -->
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition duration-200" x-on:click.stop>
                                        <button onclick="navigator.clipboard.writeText('{{ $file['assetUrl'] }}'); Flux.toast({ variant: 'success', text: 'Public URL copied to clipboard!' })"
                                                class="p-1.5 bg-white/95 dark:bg-zinc-900/95 hover:bg-white dark:hover:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 transition"
                                                title="{{ __('Copy Direct URL') }}">
                                            <flux:icon name="link" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Name / Footer -->
                                <div class="p-3 flex items-center justify-between gap-2 bg-white dark:bg-zinc-800/50">
                                    <div class="min-w-0 flex-1">
                                        <span class="font-medium text-zinc-800 dark:text-zinc-200 text-xs block truncate" title="{{ $file['name'] }}">
                                            {{ $file['name'] }}
                                        </span>
                                        <span class="text-[10px] text-zinc-400 dark:text-zinc-500 block">
                                            {{ $file['human_readable_size'] }}
                                        </span>
                                    </div>

                                    <!-- Actions Dropdown -->
                                    <div x-on:click.stop>
                                        <flux:dropdown align="end">
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" dusk="file-dropdown-{{ $file['id'] }}" />
                                            <flux:menu class="min-w-[120px]">
                                                <flux:menu.item wire:click="startFileRename({{ $file['id'] }})" icon="pencil" dusk="rename-file-{{ $file['id'] }}">{{ __('Rename') }}</flux:menu.item>
                                                <flux:menu.item x-on:click="confirmDeleteFile({{ $file['id'] }})" variant="danger" icon="trash" dusk="delete-file-{{ $file['id'] }}">{{ __('Delete') }}</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination Links -->
                    <div class="mt-6">
                        {{ $files->links() }}
                    </div>
                </div>
            @endif
        @endif
    @endif

    <!-- MODAL: Upload Files (drag & drop / browse) -->
    <flux:modal name="upload-media-modal" class="md:w-[32rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Upload Files') }}</flux:heading>
                <flux:subheading>{{ __('Add files to the current folder. They are processed and optimised automatically.') }}</flux:subheading>
            </div>

            <div
                x-data="{ drag: false }"
                x-on:dragover.prevent="drag = true"
                x-on:dragleave.prevent="drag = false"
                x-on:drop.prevent="drag = false; $refs.uploadInput.files = $event.dataTransfer.files; $refs.uploadInput.dispatchEvent(new Event('change'))"
                class="rounded-xl border-2 border-dashed p-8 text-center transition"
                :class="drag
                    ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/20'
                    : 'border-zinc-300 text-zinc-500 dark:border-zinc-600 dark:text-zinc-400'"
            >
                <flux:icon name="arrow-up-tray" variant="micro" class="mx-auto mb-2 size-8 text-primary-500" />
                <flux:text class="text-sm">{{ __('Drag & drop files here, or') }}</flux:text>
                <label class="cursor-pointer text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
                    {{ __('browse to upload') }}
                    <input type="file" multiple class="hidden" x-ref="uploadInput" wire:model="uploads" accept="image/*,application/pdf,.doc,.docx,.txt,.zip,.rar" />
                </label>
            </div>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Done') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <!-- MODAL: Create Folder -->
    <flux:modal name="create-folder-modal" class="md:w-96">
        <form wire:submit.prevent="createFolder" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Create Folder') }}</flux:heading>
                <flux:subheading>{{ __('Add a new directory inside the current folder.') }}</flux:subheading>
            </div>

            <flux:input 
                wire:model="newFolderName" 
                label="{{ __('Folder Name') }}" 
                placeholder="{{ __('e.g. Vacation Photos') }}"
                required
                autofocus
            />

            <!-- Error State -->
            @error('newFolderName')
                <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
            @enderror

            <div class="flex gap-2 justify-between">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" dusk="create-folder-submit">{{ __('Create') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- MODAL: Rename Folder -->
    <flux:modal name="rename-folder-modal" class="md:w-96">
        <form wire:submit.prevent="renameFolder" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Rename Folder') }}</flux:heading>
                <flux:subheading>{{ __('Choose a new name for this directory.') }}</flux:subheading>
            </div>

            <flux:input 
                wire:model="editingName" 
                label="{{ __('New Folder Name') }}" 
                required
                autofocus
            />

            <!-- Error State -->
            @error('editingName')
                <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
            @enderror

            <div class="flex gap-2 justify-between">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Rename') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- MODAL: Rename File -->
    <flux:modal name="rename-file-modal" class="md:w-96">
        <form wire:submit.prevent="renameFile" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Rename File') }}</flux:heading>
                <flux:subheading>{{ __('Choose a new name for this file (extension will be preserved).') }}</flux:subheading>
            </div>

            <flux:input 
                wire:model="editingName" 
                label="{{ __('New File Name') }}" 
                required
                autofocus
            />

            <!-- Error State -->
            @error('editingName')
                <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
            @enderror

            <div class="flex gap-2 justify-between">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Rename') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- MODAL: Move Items -->
    <flux:modal name="move-items-modal" class="md:w-96">
        <form wire:submit.prevent="moveSelected" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Move Items') }}</flux:heading>
                <flux:subheading>{{ __('Select a destination folder for selected items.') }}</flux:subheading>
            </div>

            <!-- Folder Select Dropdown -->
            <flux:select label="{{ __('Destination Folder') }}" wire:model="moveTargetFolderId" required>
                <option value="">{{ __('Select Folder...') }}</option>
                @if(count($selectedFolders) > 0 || count($selectedFiles) > 0)
                    @foreach($this->moveTargetFolders as $folder)
                        <option value="{{ $folder->id }}">
                            {{ str_repeat('— ', $folder->depth ?? 0) . $folder->name }}
                        </option>
                    @endforeach
                @endif
            </flux:select>

            <!-- Error State -->
            @error('moveTargetFolderId')
                <p class="text-xs text-red-500 font-semibold mt-1">{{ $message }}</p>
            @enderror

            <div class="flex gap-2 justify-between">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Move') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Media Details Slide-over (extracted into the MediaDetails component) -->
    <flux:modal name="media-details" variant="bare">
        @if($detailsMediaId)
            <livewire:media.details :media-id="$detailsMediaId" :media-ids="$navigationIds" wire:key="details-{{ $detailsMediaId }}" />
        @endif
    </flux:modal>

    {{-- @script is Livewire-managed: registered once and torn down with the
         component. The bare listener this replaced was re-added on every full
         page load, stacking duplicate handlers. --}}
    @script
    <script>
        document.addEventListener('clipboard-copy', (event) => {
            const text = event.detail?.text ?? '';
            if (text && navigator.clipboard) {
                navigator.clipboard.writeText(text).catch(() => {});
            }
        });
    </script>
    @endscript





    <!-- MODAL: Confirm Delete Folder -->
    <flux:modal name="confirm-delete-folder-modal" class="md:w-96">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Delete Folder') }}</flux:heading>
                <flux:subheading>{{ __('Are you sure you want to delete this folder and all of its contents? This action cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex gap-2 justify-between">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="danger" 
                    dusk="delete-folder-confirm"
                    x-on:click="$wire.deleteFolder(folderIdToDelete); Flux.modal('confirm-delete-folder-modal').close()"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- MODAL: Confirm Delete File -->
    <flux:modal name="confirm-delete-file-modal" class="md:w-96">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Delete File') }}</flux:heading>
                <flux:subheading>{{ __('Are you sure you want to delete this file? This action cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex gap-2 justify-between">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="danger" 
                    x-on:click="$wire.deleteFile(fileIdToDelete); Flux.modal('confirm-delete-file-modal').close(); showDrawer = false"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- MODAL: Confirm Delete Selected Items (Bulk) -->
    <flux:modal name="confirm-delete-selected-modal" class="md:w-96">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Delete Selected Items') }}</flux:heading>
                <flux:subheading>{{ __('Are you sure you want to delete all selected files and folders? This action cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex gap-2 justify-between">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="danger" 
                    x-on:click="$wire.deleteSelected(); Flux.modal('confirm-delete-selected-modal').close()"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
