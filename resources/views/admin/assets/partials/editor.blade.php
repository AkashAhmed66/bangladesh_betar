{{-- Floating audio editor: icon toolbar (right edge) + draggable tool dialogs.
     Heavy effects render into the main waveform; the master is never modified. --}}
@php $loadedVersion = $versions->firstWhere('id', $selectedVersionId); $isMaster = $loadedVersion?->version_type === 'preservation_master'; @endphp
<div x-data="audioEditor()" x-effect="applyPreview(); schedulePreview()">

    {{-- ------------------------------ Toolbar ------------------------------ --}}
    {{-- Docked full-height rail on the right edge (below the sticky 4rem topbar).
         Fixed so it stays put while the page scrolls; the studio content reserves
         a matching pr-16 gutter so the rail never overlaps other sections. --}}
    <div class="fixed right-0 top-16 bottom-0 z-40 flex w-16 flex-col items-center gap-0.5 overflow-y-auto border-l border-slate-200 bg-white/95 p-1.5 shadow-xl backdrop-blur scrollbar-slim dark:border-slate-700 dark:bg-slate-900/95">
        <p class="pb-0.5 text-[9px] font-semibold uppercase tracking-wider text-slate-400">Edit</p>

        <div class="group relative">
            <button @click="toggleTool('volume')" class="tool-btn" :class="btnClass('volume')"><x-icon name="wave" class="size-5" /></button>
            <span x-show="toolDot('volume')" x-cloak class="absolute right-0.5 top-0.5 size-2 rounded-full bg-accent-500"></span>
            <span class="tool-tip">Volume &amp; Dynamics</span>
        </div>
        <div class="group relative">
            <button @click="toggleTool('eq')" class="tool-btn" :class="btnClass('eq')"><x-icon name="chart-bar" class="size-5" /></button>
            <span x-show="toolDot('eq')" x-cloak class="absolute right-0.5 top-0.5 size-2 rounded-full bg-accent-500"></span>
            <span class="tool-tip">Equalizer</span>
        </div>
        <div class="group relative">
            <button @click="toggleTool('restore')" class="tool-btn" :class="btnClass('restore')"><x-icon name="sparkles" class="size-5" /></button>
            <span x-show="toolDot('restore')" x-cloak class="absolute right-0.5 top-0.5 size-2 rounded-full bg-accent-500"></span>
            <span class="tool-tip">Restoration</span>
        </div>
        <div class="group relative">
            <button @click="toggleTool('pitch')" class="tool-btn" :class="btnClass('pitch')"><x-icon name="music" class="size-5" /></button>
            <span x-show="toolDot('pitch')" x-cloak class="absolute right-0.5 top-0.5 size-2 rounded-full bg-accent-500"></span>
            <span class="tool-tip">Pitch &amp; Tempo</span>
        </div>
        <div class="group relative">
            <button @click="toggleTool('export')" class="tool-btn" :class="btnClass('export')"><x-icon name="download" class="size-5" /></button>
            <span class="tool-tip">Export format</span>
        </div>

        <div class="my-1 h-px w-6 bg-slate-200 dark:bg-slate-700"></div>

        <div class="group relative">
            <button @click="toggleTool('chain')" class="tool-btn" :class="btnClass('chain')"><x-icon name="queue" class="size-5" /></button>
            <span x-show="activeCount()>0" x-cloak class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-primary-600 text-[9px] font-bold text-white" x-text="activeCount()"></span>
            <span class="tool-tip">Processing chain</span>
        </div>
        <div class="group relative">
            <button @click="reset()" class="tool-btn text-slate-500 hover:bg-slate-100 hover:text-rose-600 dark:text-slate-400 dark:hover:bg-slate-800"><x-icon name="arrow-path" class="size-5" /></button>
            <span class="tool-tip">Reset all</span>
        </div>
        <div class="group relative">
            <button @click="toggleTool('save')" class="tool-btn text-white" :class="openTool==='save' ? 'bg-primary-700' : 'bg-primary-600 hover:bg-primary-700'"><x-icon name="check-badge" class="size-5" /></button>
            <span class="tool-tip">Save version</span>
        </div>
    </div>

    {{-- ---------------------------- Draggable dialog ---------------------------- --}}
    <div x-show="openTool" x-cloak :style="`left:${dialogX}px; top:${dialogY}px`"
         class="fixed z-50 w-80 rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
        <div class="flex cursor-move items-center justify-between rounded-t-xl border-b border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800" @mousedown="startDrag($event)">
            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200" x-text="toolLabel()"></span>
            <button @click="openTool=null" @mousedown.stop class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><x-icon name="x" class="size-4" /></button>
        </div>
        <div class="max-h-[70vh] space-y-3 overflow-y-auto scrollbar-slim p-4">

            {{-- Volume & Dynamics --}}
            <div x-show="openTool==='volume'" class="space-y-3">
                <div>
                    <label class="flex justify-between text-xs text-slate-500"><span>Gain</span><span x-text="gain + ' dB'"></span></label>
                    <input type="range" min="-20" max="20" step="0.5" x-model.number="gain" class="w-full">
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="normalize" class="rounded"> Normalize loudness</label>
                <div class="flex items-center gap-2 text-xs text-slate-500" :class="!normalize && 'opacity-40'">
                    <span>Target</span><input type="number" step="1" x-model.number="normalizeTarget" class="form-input py-1 w-20 text-xs"> LUFS
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="compress" class="rounded"> Compressor</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="limit" class="rounded"> Limiter</label>
                </div>
                <p class="text-[11px] text-slate-400">Gain / compressor / limiter preview instantly; normalize renders into the waveform.</p>
            </div>

            {{-- Equalizer --}}
            <div x-show="openTool==='eq'" class="space-y-3">
                <template x-for="band in [['bass','Bass'],['mid','Mid'],['treble','Treble']]" :key="band[0]">
                    <div>
                        <label class="flex justify-between text-xs text-slate-500"><span x-text="band[1]"></span><span x-text="eq[band[0]] + ' dB'"></span></label>
                        <input type="range" min="-15" max="15" step="1" x-model.number="eq[band[0]]" class="w-full">
                    </div>
                </template>
                <div class="flex flex-wrap gap-1">
                    <button class="btn-ghost btn-sm" @click="preset('voice')">Voice</button>
                    <button class="btn-ghost btn-sm" @click="preset('warm')">Warm</button>
                    <button class="btn-ghost btn-sm" @click="preset('bright')">Bright</button>
                    <button class="btn-ghost btn-sm" @click="preset('flat')">Flat</button>
                </div>
                <p class="text-[11px] text-slate-400">Previews instantly on the main player.</p>
            </div>

            {{-- Restoration --}}
            <div x-show="openTool==='restore'" class="space-y-3">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="denoise" class="rounded"> Noise reduction</label>
                <div class="flex items-center gap-2 text-xs text-slate-500" :class="!denoise && 'opacity-40'">
                    <span>Strength</span><input type="range" min="0" max="1" step="0.1" x-model.number="denoiseStrength" class="flex-1"><span x-text="Math.round(denoiseStrength*100)+'%'"></span>
                </div>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="dehum" class="rounded"> De-hum</label>
                    <select x-model.number="dehumFreq" class="form-input py-1 w-24 text-xs" :class="!dehum && 'opacity-40'"><option :value="50">50 Hz</option><option :value="60">60 Hz</option></select>
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="declick" class="rounded"> De-click</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="declip" class="rounded"> De-clip</label>
                </div>
                <p class="text-[11px] text-slate-400">Denoise / de-click / de-clip render into the waveform (~1s).</p>
            </div>

            {{-- Pitch & Tempo --}}
            <div x-show="openTool==='pitch'" class="space-y-3">
                <div>
                    <label class="flex justify-between text-xs text-slate-500"><span>Pitch</span><span x-text="pitch + ' st'"></span></label>
                    <input type="range" min="-12" max="12" step="1" x-model.number="pitch" class="w-full">
                </div>
                <div>
                    <label class="flex justify-between text-xs text-slate-500"><span>Tempo (keeps pitch)</span><span x-text="tempo + '×'"></span></label>
                    <input type="range" min="0.5" max="2" step="0.05" x-model.number="tempo" class="w-full">
                </div>
                <p class="text-[11px] text-slate-400">Pitch renders into the waveform; tempo previews instantly.</p>
            </div>


            {{-- Export --}}
            <div x-show="openTool==='export'" class="space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <label class="text-xs text-slate-500">Format
                        <select x-model="format" class="form-input py-1 text-xs"><option>wav</option><option>flac</option><option>mp3</option><option>aac</option><option>ogg</option></select>
                    </label>
                    <label class="text-xs text-slate-500">Bit depth
                        <select x-model.number="bitDepth" class="form-input py-1 text-xs"><option :value="16">16-bit</option><option :value="24">24-bit</option></select>
                    </label>
                    <label class="text-xs text-slate-500">Sample rate
                        <select x-model.number="sampleRate" class="form-input py-1 text-xs"><option :value="0">keep</option><option :value="22050">22.05k</option><option :value="44100">44.1k</option><option :value="48000">48k</option></select>
                    </label>
                    <label class="text-xs text-slate-500">Channels
                        <select x-model="channels" class="form-input py-1 text-xs"><option value="">keep</option><option value="mono">Mono</option><option value="stereo">Stereo</option></select>
                    </label>
                    <label class="text-xs text-slate-500" x-show="['mp3','aac','ogg'].includes(format)">Bitrate
                        <select x-model.number="bitrate" class="form-input py-1 text-xs"><option :value="128">128k</option><option :value="192">192k</option><option :value="320">320k</option></select>
                    </label>
                </div>
                <p class="text-[11px] text-slate-400">Applied on save.</p>
            </div>

            {{-- Processing chain --}}
            <div x-show="openTool==='chain'" class="space-y-2">
                <ol class="max-h-56 space-y-1 overflow-y-auto scrollbar-slim text-sm">
                    <template x-for="(item,i) in chain()" :key="i">
                        <li class="flex items-center gap-2 rounded bg-slate-50 px-2 py-1 dark:bg-slate-800/60">
                            <span class="text-xs text-slate-400" x-text="(i+1)+'.'"></span>
                            <span class="text-slate-700 dark:text-slate-200" x-text="item"></span>
                        </li>
                    </template>
                    <template x-if="chain().length===0"><li class="text-slate-400">No operations selected yet.</li></template>
                </ol>
                <label class="flex items-center gap-2 border-t border-slate-100 pt-2 text-xs text-slate-500 dark:border-slate-800"><input type="checkbox" x-model="autoPreview" class="rounded"> Auto-render heavy effects into the waveform</label>
                <button type="button" class="btn-secondary btn-sm w-full" @click="runPreview(heavyOps())">Render preview now</button>
                <p class="text-xs text-rose-500" x-show="previewErr" x-text="previewErr"></p>
            </div>

            {{-- Save --}}
            <div x-show="openTool==='save'" class="space-y-3">
                <input x-model="title" class="form-input text-sm" placeholder="Version name (optional)">
                <div class="flex rounded-lg border border-slate-200 p-0.5 text-xs dark:border-slate-700">
                    <button type="button" class="flex-1 rounded-md py-1.5" :class="target==='new' ? 'bg-primary-700 text-white' : 'text-slate-500'" @click="target='new'">New version</button>
                    <button type="button" class="flex-1 rounded-md py-1.5" :disabled="isMaster"
                            :class="[target==='current' ? 'bg-primary-700 text-white' : 'text-slate-500', isMaster && 'opacity-40 cursor-not-allowed']"
                            @click="!isMaster && (target='current')" x-text="isMaster ? 'This version (locked)' : 'Update this version'"></button>
                </div>
                <button class="btn-primary w-full" :disabled="rendering || activeCount()===0" @click="doSave()">
                    <span x-show="!rendering"><x-icon name="check-badge" class="size-4" /> <span x-text="target==='current' ? 'Save to this version' : 'Save as new version'"></span></span>
                    <span x-show="rendering">Saving… <span x-text="progress + '%'"></span></span>
                </button>
                <template x-if="rendering">
                    <div class="h-1.5 rounded-full bg-slate-200 dark:bg-slate-700"><div class="h-1.5 rounded-full bg-primary-600 transition-all" :style="`width:${progress}%`"></div></div>
                </template>
                <p class="rounded bg-emerald-50 px-3 py-2 text-xs text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300" x-show="resultMsg" x-text="resultMsg" x-cloak></p>
                <p class="rounded bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" x-show="errorMsg" x-text="errorMsg" x-cloak></p>
                <p class="text-[11px] text-slate-400">The preservation master is never modified — a render always creates a new version (or updates a non-master one). Runs server-side with ffmpeg.</p>
            </div>
        </div>
    </div>
</div>

<script>
    function audioEditor() {
        return {
            title: '',
            livePreview: true, target: 'new', isMaster: {{ $isMaster ? 'true' : 'false' }},
            autoPreview: true, previewLoading: false, previewErr: '', _pvTimer: null, _pvKey: '', _previewLoaded: false,
            openTool: null, dialogX: 0, dialogY: 0,
            editsRev: 0,   // bumped by the inline waveform editor so the chain/save button react to cuts & trim
            gain: 0, normalize: false, normalizeTarget: -16, compress: false, limit: false,
            eq: { bass: 0, mid: 0, treble: 0 },
            denoise: false, denoiseStrength: 0.5, dehum: false, dehumFreq: 50, declick: false, declip: false,
            pitch: 0, tempo: 1,
            reverse: false, fadeIn: 0, fadeOut: 0,
            silenceRemove: false, silenceThreshold: -40, silenceGap: 0.5,
            format: 'wav', bitDepth: 24, sampleRate: 0, channels: '', bitrate: 192,
            rendering: false, progress: 0, statusText: '', resultMsg: '', errorMsg: '',

            init() {
                // The waveform editor (studio.js) lives outside Alpine; on each edit
                // change, refresh the chain/Save button AND (re)render the effect
                // preview into the waveform so changes are visible before saving.
                window.addEventListener('studio-edits-changed', () => { this.editsRev++; this.schedulePreview(); });
            },

            /* ---- floating dialog ---- */
            toggleTool(id) {
                if (this.openTool === id) { this.openTool = null; return; }
                if (!this.openTool) { this.dialogX = Math.max(16, window.innerWidth - 420); this.dialogY = 130; }
                this.openTool = id;
            },
            toolLabel() {
                return ({ volume: 'Volume & Dynamics', eq: 'Equalizer', restore: 'Restoration',
                    pitch: 'Pitch & Tempo', export: 'Export Format', chain: 'Processing Chain', save: 'Save Version' })[this.openTool] || '';
            },
            btnClass(id) {
                return this.openTool === id ? 'bg-primary-600 text-white'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-primary-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-primary-300';
            },
            toolDot(id) {
                const m = {
                    volume: () => this.gain || this.normalize || this.compress || this.limit,
                    eq: () => this.eq.bass || this.eq.mid || this.eq.treble,
                    restore: () => this.denoise || this.dehum || this.declick || this.declip,
                    pitch: () => this.pitch || this.tempo !== 1,
                };
                return m[id] ? !!m[id]() : false;
            },
            startDrag(e) {
                const dx = e.clientX - this.dialogX, dy = e.clientY - this.dialogY;
                const move = (ev) => {
                    this.dialogX = Math.max(0, Math.min(window.innerWidth - 80, ev.clientX - dx));
                    this.dialogY = Math.max(56, Math.min(window.innerHeight - 40, ev.clientY - dy));
                };
                const up = () => { window.removeEventListener('mousemove', move); window.removeEventListener('mouseup', up); };
                window.addEventListener('mousemove', move); window.addEventListener('mouseup', up);
            },

            /* ---- presets ---- */
            preset(name) {
                const p = { voice: [-3, 3, 2], warm: [4, 0, -3], bright: [-2, 1, 5], flat: [0, 0, 0] }[name];
                this.eq = { bass: p[0], mid: p[1], treble: p[2] };
            },

            /* ---- op chain ---- */
            ops() {
                const o = [];
                // Editing ops (delete/crop/silence/fade/volume) come from the
                // inline waveform editor (studio.js), already in render format.
                const edits = window.studioGetEdits ? window.studioGetEdits() : { ops: [] };
                (edits.ops || []).forEach((e) => o.push(e));
                if (this.reverse) o.push({ op: 'reverse' });
                if (this.declip) o.push({ op: 'declip' });
                if (this.declick) o.push({ op: 'declick' });
                if (this.denoise) o.push({ op: 'denoise', strength: +this.denoiseStrength });
                if (this.dehum) o.push({ op: 'dehum', freq: +this.dehumFreq });
                if (this.eq.bass || this.eq.mid || this.eq.treble) o.push({ op: 'eq', bass: +this.eq.bass, mid: +this.eq.mid, treble: +this.eq.treble });
                if (this.compress) o.push({ op: 'compress' });
                if (this.limit) o.push({ op: 'limit' });
                if (+this.gain) o.push({ op: 'gain', db: +this.gain });
                if (this.normalize) o.push({ op: 'normalize', target_lufs: +this.normalizeTarget });
                if (+this.pitch) o.push({ op: 'pitch', semitones: +this.pitch });
                if (+this.tempo !== 1) o.push({ op: 'tempo', factor: +this.tempo });
                if (this.silenceRemove) o.push({ op: 'silence_remove', threshold_db: +this.silenceThreshold, min_gap: +this.silenceGap });
                if (+this.fadeIn > 0) o.push({ op: 'fade', dir: 'in', duration: +this.fadeIn });
                if (+this.fadeOut > 0) o.push({ op: 'fade', dir: 'out', duration: +this.fadeOut });
                if (this.channels) o.push({ op: 'channels', layout: this.channels });
                if (+this.sampleRate) o.push({ op: 'resample', rate: +this.sampleRate });
                o.push({ op: 'export', format: this.format, bit_depth: +this.bitDepth, bitrate: +this.bitrate });
                return o;
            },
            activeCount() { void this.editsRev; return this.ops().filter((o) => o.op !== 'export').length; },
            chain() {
                void this.editsRev;
                const label = {
                    trim: (o) => `Crop ${o.start}–${o.end}s`, cut: (o) => `Cut ${o.start}–${o.end}s`,
                    silence: (o) => `Silence ${o.start}–${o.end}s`,
                    reverse: () => 'Reverse', declip: () => 'De-clip', declick: () => 'De-click',
                    denoise: (o) => `Noise reduction ${Math.round(o.strength * 100)}%`, dehum: (o) => `De-hum ${o.freq}Hz`,
                    eq: (o) => `EQ b${o.bass}/m${o.mid}/t${o.treble}`, compress: () => 'Compressor', limit: () => 'Limiter',
                    gain: (o) => o.start != null ? `Volume ${o.db > 0 ? '+' : ''}${o.db}dB @ ${o.start}–${o.end}s` : `Gain ${o.db}dB`,
                    normalize: (o) => `Normalize ${o.target_lufs} LUFS`,
                    pitch: (o) => `Pitch ${o.semitones}st`, tempo: (o) => `Tempo ${o.factor}×`,
                    silence_remove: () => 'Remove silence',
                    fade: (o) => o.start != null ? `Fade ${o.dir} ${o.start}–${o.end}s` : `Fade ${o.dir} ${o.duration}s`,
                    channels: (o) => `Channels ${o.layout}`, resample: (o) => `Resample ${o.rate}Hz`,
                    export: (o) => `Export ${o.format.toUpperCase()}`,
                };
                return this.ops().map((o) => (label[o.op] ? label[o.op](o) : o.op));
            },
            reset() {
                window.studioClearEdits?.();   // clear cuts/trim on the waveform
                Object.assign(this, { gain: 0, normalize: false,
                    compress: false, limit: false, eq: { bass: 0, mid: 0, treble: 0 }, denoise: false, dehum: false,
                    declick: false, declip: false, pitch: 0, tempo: 1, reverse: false, fadeIn: 0, fadeOut: 0,
                    silenceRemove: false, resultMsg: '', errorMsg: '' });
            },

            /* ---- live preview + auto render into waveform ---- */
            applyPreview() {
                window.studioPreview?.({
                    enabled: this.livePreview,
                    gain: +this.gain, bass: +this.eq.bass, mid: +this.eq.mid, treble: +this.eq.treble,
                    dehum: this.dehum, dehumFreq: +this.dehumFreq,
                    compress: this.compress, limit: this.limit, tempo: +this.tempo,
                });
            },
            heavyOps() {
                // The whole edit chain (except export) renders INTO the waveform so
                // the wave shows the LIVE result — cut merges, crop shortens, effects
                // bake in. Nothing is applied until you Save.
                return this.ops().filter((o) => o.op !== 'export');
            },
            schedulePreview() {
                if (!this.autoPreview) return;
                const heavy = this.heavyOps();
                const key = JSON.stringify(heavy);
                if (key === this._pvKey) return;
                this._pvKey = key;
                if (heavy.length === 0 && !this._previewLoaded) return; // nothing loaded yet — skip
                clearTimeout(this._pvTimer);
                this._pvTimer = setTimeout(() => this.runPreview(heavy), 900);
            },
            async runPreview(ops) {
                this.previewErr = '';
                if (!ops || ops.length === 0) {                 // revert to original if a preview was loaded
                    if (this._previewLoaded) { await window.studioLoadAudio?.(window.studioOriginalUrl?.()); this._previewLoaded = false; }
                    return;
                }
                window.studioSetBusy?.(true);
                this.previewLoading = true;
                const r = await window.studioPreviewRender?.(ops, 0);
                this.previewLoading = false;
                if (r && r.url) { await window.studioLoadAudio?.(r.url); this._previewLoaded = true; }
                else { window.studioSetBusy?.(false); if (r && r.error) this.previewErr = r.error; }
            },

            /* ---- save ---- */
            doSave() {
                this.rendering = true; this.progress = 0; this.resultMsg = ''; this.errorMsg = ''; this.statusText = 'queued';
                window.studioRender(this.ops(), this.title, {
                    onProgress: (p, s) => { this.progress = p; this.statusText = s; },
                    onDone: (v) => { this.rendering = false; this.progress = 100;
                        this.resultMsg = this.target === 'current' ? `Saved to “${v.label}”. Reloading…` : `Saved as new version “${v.label}”. Reloading…`;
                        setTimeout(() => location.reload(), 1600); },
                    onError: (e) => { this.rendering = false; this.errorMsg = e; },
                }, this.target);
            },
        };
    }
</script>
