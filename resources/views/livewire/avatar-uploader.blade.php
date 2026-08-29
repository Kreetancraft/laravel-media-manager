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
                {{-- A plain file input: the label is the button, so there is no
                     second control to keep in step with it. --}}
                <flux:button size="sm" icon="arrow-up-tray" variant="filled" as="label" for="{{ $this->getId() }}-upload">
                    {{ $currentUrl ? __('Replace') : __('Upload') }}
                </flux:button>

                <input
                    id="{{ $this->getId() }}-upload"
                    type="file"
                    accept="image/*"
                    wire:model="upload"
                    class="sr-only"
                />

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
