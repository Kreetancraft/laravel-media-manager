{{-- Upload one image, no library browsing. See AvatarUploader for why. --}}
<div class="space-y-3 rounded-xl border border-zinc-200 bg-zinc-50/40 p-4 dark:border-zinc-800/60 dark:bg-zinc-900/20">
    <div class="flex items-center gap-4">
        @if ($currentUrl)
            <img
                src="{{ $currentUrl }}"
                alt="{{ __('Current image') }}"
                class="size-16 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-700"
            />
        @else
            <div class="flex size-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="user" class="size-6 text-zinc-400" />
            </div>
        @endif

        <div class="min-w-0 space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                {{-- A real <label> wrapping the input, styled to look like a
                     Flux button.

                     Not <flux:button as="label" for="...">: Flux renders a
                     <button>, `for` means nothing on one, and both Upload and
                     Replace did nothing at all. Wrapping needs no `for` either,
                     so there is no id to keep in step. --}}
                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-800 shadow-xs transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600">
                    <flux:icon name="arrow-up-tray" variant="micro" />
                    <span>{{ $currentUrl ? __('Replace') : __('Upload') }}</span>

                    <input type="file" accept="image/*" wire:model="upload" class="sr-only" />
                </label>

                @if ($currentUrl)
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="remove">
                        {{ __('Remove') }}
                    </flux:button>
                @endif
            </div>

            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400" wire:loading.remove wire:target="upload">
                {{ __('A square image works best.') }}
            </flux:text>

            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400" wire:loading wire:target="upload">
                {{ __('Uploading…') }}
            </flux:text>
        </div>
    </div>

    <flux:error name="upload" />
</div>
