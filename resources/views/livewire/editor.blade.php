<div x-data="mediaEditor(@js([
    'sourceUrl' => $sourceUrl,
    'origWidth' => $origWidth,
    'origHeight' => $origHeight,
    'rotate' => $rotate,
    'flip' => $flip,
    'zoom' => $zoom,
    'brightness' => $brightness,
    'contrast' => $contrast,
]))" x-init="init()" class="max-w-6xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route(config('media.routes.names.index', 'admin.media')) }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">&larr; {{ __('Back to Media') }}</a>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mt-1">{{ __('Edit Image') }}</h1>
        </div>
        <button type="button" @click="save()" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-60">
            {{ __('Save changes') }}
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-6">
        {{-- Canvas / preview --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 p-4 flex items-center justify-center min-h-[360px]">
            <canvas x-ref="canvas"
                    class="max-w-full cursor-crosshair rounded-md shadow-sm bg-white dark:bg-zinc-800"
                    @mousedown="startCrop($event)"
                    @mousemove="moveCrop($event)"
                    @mouseup="endCrop($event)"
                    @mouseleave="endCrop($event)"></canvas>
        </div>

        {{-- Controls --}}
        <div class="space-y-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2">{{ __('Rotate') }}</p>
                <div class="flex gap-2">
                    <button type="button" @click="rotateLeft()" class="flex-1 rounded-md border border-zinc-200 dark:border-zinc-700 px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Left') }}</button>
                    <button type="button" @click="rotateRight()" class="flex-1 rounded-md border border-zinc-200 dark:border-zinc-700 px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Right') }}</button>
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2">{{ __('Flip') }}</p>
                <div class="flex gap-2">
                    <button type="button" @click="toggleFlip('horizontal')" class="flex-1 rounded-md border border-zinc-200 dark:border-zinc-700 px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800" :class="{ 'bg-indigo-50 border-indigo-300 text-indigo-700 dark:bg-indigo-500/10 dark:border-indigo-500/40 dark:text-indigo-300': flip === 'horizontal' }">{{ __('Horizontal') }}</button>
                    <button type="button" @click="toggleFlip('vertical')" class="flex-1 rounded-md border border-zinc-200 dark:border-zinc-700 px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800" :class="{ 'bg-indigo-50 border-indigo-300 text-indigo-700 dark:bg-indigo-500/10 dark:border-indigo-500/40 dark:text-indigo-300': flip === 'vertical' }">{{ __('Vertical') }}</button>
                </div>
            </div>

            <div>
                <div class="flex justify-between">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Brightness') }}</label>
                    <span class="text-xs text-zinc-400" x-text="brightness"></span>
                </div>
                <input type="range" min="-100" max="100" step="1" x-model="brightness" @input="scheduleBase()" class="w-full mt-1">
            </div>

            <div>
                <div class="flex justify-between">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Contrast') }}</label>
                    <span class="text-xs text-zinc-400" x-text="contrast"></span>
                </div>
                <input type="range" min="-100" max="100" step="1" x-model="contrast" @input="scheduleBase()" class="w-full mt-1">
            </div>

            <div>
                <div class="flex justify-between">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Zoom') }}</label>
                    <span class="text-xs text-zinc-400" x-text="zoom.toFixed(1) + 'x'"></span>
                </div>
                <input type="range" min="0.5" max="3" step="0.1" x-model="zoom" @input="scheduleBase()" class="w-full mt-1">
            </div>

            <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <button type="button" @click="clearCrop()" class="w-full rounded-md border border-zinc-200 dark:border-zinc-700 px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Clear crop selection') }}</button>
            </div>

            <p class="text-[11px] text-zinc-400 leading-relaxed">{{ __('Drag on the image to select a crop region. Rotation, flip, zoom and colour adjustments are applied on save.') }}</p>
        </div>
    </div>

    <script>
        window.mediaEditor = function (config) {
            return {
                sourceUrl: config.sourceUrl,
                origWidth: config.origWidth,
                origHeight: config.origHeight,
                rotate: config.rotate,
                flip: config.flip,
                zoom: config.zoom,
                brightness: config.brightness,
                contrast: config.contrast,
                crop: {},
                image: null,
                selection: null,
                dragging: false,
                start: null,
                box: 560,
                baseCanvas: null,
                rafId: null,

                init() {
                    this.baseCanvas = document.createElement('canvas');
                    this.image = new Image();
                    this.image.onload = () => this.scheduleBase();
                    this.image.src = this.sourceUrl;
                },

                rotatedDims() {
                    const swapped = Math.abs(this.rotate) === 90 || Math.abs(this.rotate) === 270;
                    let w = swapped ? this.origHeight : this.origWidth;
                    let h = swapped ? this.origWidth : this.origHeight;
                    w = Math.round(w * this.zoom);
                    h = Math.round(h * this.zoom);

                    return { w, h };
                },

                displayScale() {
                    const { w, h } = this.rotatedDims();

                    return Math.min(this.box / w, this.box / h);
                },

                rotateLeft() {
                    this.rotate = this.rotate === -90 ? 270 : this.rotate - 90;
                    this.scheduleBase();
                },

                rotateRight() {
                    this.rotate = this.rotate === 270 ? -90 : this.rotate + 90;
                    this.scheduleBase();
                },

                toggleFlip(direction) {
                    this.flip = this.flip === direction ? '' : direction;
                    this.scheduleBase();
                },

                // Re-render the transformed base image to an offscreen canvas.
                // Throttled with requestAnimationFrame so rapid slider/button
                // interactions never queue more than one expensive draw.
                scheduleBase() {
                    if (this.rafId) {
                        cancelAnimationFrame(this.rafId);
                    }
                    this.rafId = requestAnimationFrame(() => this.renderBase());
                },

                renderBase() {
                    if (! this.image || ! this.image.complete) {
                        return;
                    }

                    const scale = this.displayScale();
                    const { w, h } = this.rotatedDims();

                    this.baseCanvas.width = Math.round(w * scale);
                    this.baseCanvas.height = Math.round(h * scale);

                    const ctx = this.baseCanvas.getContext('2d');
                    ctx.clearRect(0, 0, this.baseCanvas.width, this.baseCanvas.height);
                    ctx.filter = `brightness(${100 + Number(this.brightness)}%) contrast(${100 + Number(this.contrast)}%)`;
                    ctx.save();
                    ctx.translate(this.baseCanvas.width / 2, this.baseCanvas.height / 2);

                    if (this.rotate === 90) {
                        ctx.rotate(Math.PI / 2);
                    } else if (this.rotate === 180) {
                        ctx.rotate(Math.PI);
                    } else if (this.rotate === -90 || this.rotate === 270) {
                        ctx.rotate(-Math.PI / 2);
                    }

                    if (this.flip === 'horizontal') {
                        ctx.scale(-1, 1);
                    }
                    if (this.flip === 'vertical') {
                        ctx.scale(1, -1);
                    }

                    ctx.drawImage(this.image, -this.baseCanvas.width / 2, -this.baseCanvas.height / 2, this.baseCanvas.width, this.baseCanvas.height);
                    ctx.restore();
                    ctx.filter = 'none';

                    this.composite();
                },

                // Cheap redraw: blit the offscreen base + overlay the crop rect.
                // Used during crop dragging so we never re-apply filters/transforms.
                composite() {
                    const canvas = this.$refs.canvas;
                    const scale = this.displayScale();
                    const { w, h } = this.rotatedDims();

                    canvas.width = Math.round(w * scale);
                    canvas.height = Math.round(h * scale);

                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(this.baseCanvas, 0, 0);

                    if (this.selection) {
                        ctx.strokeStyle = 'rgba(99,102,241,0.9)';
                        ctx.lineWidth = 2;
                        ctx.setLineDash([6, 4]);
                        ctx.strokeRect(this.selection.x, this.selection.y, this.selection.w, this.selection.h);
                        ctx.fillStyle = 'rgba(99,102,241,0.15)';
                        ctx.fillRect(this.selection.x, this.selection.y, this.selection.w, this.selection.h);
                    }
                },

                canvasPoint(event) {
                    const rect = this.$refs.canvas.getBoundingClientRect();

                    return {
                        x: event.clientX - rect.left,
                        y: event.clientY - rect.top,
                    };
                },

                startCrop(event) {
                    this.dragging = true;
                    this.start = this.canvasPoint(event);
                    this.selection = { x: this.start.x, y: this.start.y, w: 0, h: 0 };
                },

                moveCrop(event) {
                    if (! this.dragging) {
                        return;
                    }
                    const p = this.canvasPoint(event);
                    this.selection = {
                        x: Math.min(this.start.x, p.x),
                        y: Math.min(this.start.y, p.y),
                        w: Math.abs(p.x - this.start.x),
                        h: Math.abs(p.y - this.start.y),
                    };
                    this.composite();
                },

                endCrop() {
                    if (! this.dragging) {
                        return;
                    }
                    this.dragging = false;

                    if (this.selection && (this.selection.w < 5 || this.selection.h < 5)) {
                        this.selection = null;
                        this.composite();

                        return;
                    }

                    this.commitCrop();
                },

                commitCrop() {
                    if (! this.selection) {
                        this.crop = {};

                        return;
                    }

                    const scale = this.displayScale();
                    this.crop = {
                        x: Math.round(this.selection.x / scale),
                        y: Math.round(this.selection.y / scale),
                        width: Math.round(this.selection.w / scale),
                        height: Math.round(this.selection.h / scale),
                    };
                },

                clearCrop() {
                    this.selection = null;
                    this.crop = {};
                    this.composite();
                },

                save() {
                    const payload = {
                        rotate: this.rotate || null,
                        flip: this.flip || null,
                        brightness: this.brightness || null,
                        contrast: this.contrast || null,
                        zoom: this.zoom !== 1 ? this.zoom : null,
                        crop: this.crop && Object.keys(this.crop).length ? this.crop : null,
                    };

                    this.$wire.call('save', payload);
                },
            };
        };
    </script>
</div>
