<x-filament-panels::page>
{{--
    Minecraft Version Changer — fully self-contained UI.
    Pelican's Tailwind build does not scan plugin blades, so this view relies on
    its own scoped stylesheet instead of utility classes. Accent colours are read
    from the panel theme via Filament's --primary-* CSS variables (with fallbacks),
    so it adapts to whatever theme the panel uses, in light and dark mode.
--}}
<style>
    .vc {
        --vc-accent:        rgb(var(--primary-500, 59 130 246));
        --vc-accent-strong: rgb(var(--primary-600, 37 99 235));
        --vc-accent-text:   rgb(var(--primary-600, 37 99 235));
        --vc-accent-soft:   rgb(var(--primary-500, 59 130 246) / .12);
        --vc-accent-ring:   rgb(var(--primary-500, 59 130 246) / .35);
        --vc-surface:   #ffffff;
        --vc-surface-2: #f9fafb;
        --vc-inset:     #f3f4f6;
        --vc-border:    rgba(17, 24, 39, .08);
        --vc-border-2:  rgba(17, 24, 39, .14);
        --vc-text:      #111827;
        --vc-muted:     #6b7280;
        --vc-faint:     #9ca3af;
        --vc-success:   #10b981;
        --vc-danger:    #ef4444;
        --vc-warning:   #f59e0b;
        --vc-radius:    .85rem;
    }
    .dark .vc {
        --vc-accent-text: rgb(var(--primary-400, 96 165 250));
        --vc-surface:   rgba(255, 255, 255, .025);
        --vc-surface-2: rgba(255, 255, 255, .045);
        --vc-inset:     rgba(255, 255, 255, .04);
        --vc-border:    rgba(255, 255, 255, .08);
        --vc-border-2:  rgba(255, 255, 255, .16);
        --vc-text:      #f3f4f6;
        --vc-muted:     #9ca3af;
        --vc-faint:     #6b7280;
    }

    .vc { color: var(--vc-text); display: flex; flex-direction: column; gap: 1rem; }
    .vc * { box-sizing: border-box; }
    .vc-muted { color: var(--vc-muted); }
    .vc svg { display: block; }

    .vc-panel {
        background: var(--vc-surface);
        border: 1px solid var(--vc-border);
        border-radius: var(--vc-radius);
        padding: 1.1rem 1.25rem;
    }
    .vc-panel-flush { padding: 0; overflow: hidden; }

    .vc-panel-head { display: flex; align-items: center; gap: .6rem; }
    .vc-panel-head .vc-ico { width: 1.15rem; height: 1.15rem; color: var(--vc-accent-text); flex-shrink: 0; }
    .vc-panel-title { font-size: 1rem; font-weight: 700; margin: 0; letter-spacing: -.01em; }
    .vc-panel-sub { font-size: .8rem; color: var(--vc-muted); margin: .15rem 0 0; }

    /* banner */
    .vc-banner {
        display: flex; align-items: center; gap: .7rem;
        border-radius: var(--vc-radius); padding: .8rem 1rem; font-size: .82rem;
        border: 1px solid rgb(245 158 11 / .35);
        background: rgb(245 158 11 / .1);
    }
    .vc-banner .vc-ico { width: 1.25rem; height: 1.25rem; color: var(--vc-warning); flex-shrink: 0; }
    .vc-banner b { font-weight: 700; }

    /* hero / current version */
    .vc-hero { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
    .vc-hero-icon {
        width: 3.25rem; height: 3.25rem; border-radius: .8rem; object-fit: contain;
        padding: .45rem; background: var(--vc-inset); border: 1px solid var(--vc-border); flex-shrink: 0;
    }
    .vc-hero-label { font-size: .68rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: var(--vc-muted); }
    .vc-hero-title { font-size: 1.3rem; font-weight: 800; letter-spacing: -.02em; margin: .2rem 0 0; }
    .vc-hero-title span { color: var(--vc-accent-text); }
    .vc-hero-meta { margin-left: auto; text-align: right; }

    /* badges */
    .vc-badge {
        display: inline-flex; align-items: center; gap: .3rem; padding: .12rem .5rem;
        border-radius: 999px; font-size: .68rem; font-weight: 600; line-height: 1.2; white-space: nowrap;
        border: 1px solid transparent;
    }
    .vc-badge .vc-dot { width: .4rem; height: .4rem; border-radius: 50%; background: currentColor; }
    .vc-badge-success { color: var(--vc-success); background: rgb(16 185 129 / .12); border-color: rgb(16 185 129 / .25); }
    .vc-badge-accent  { color: var(--vc-accent-text); background: var(--vc-accent-soft); border-color: var(--vc-accent-ring); }
    .vc-badge-info    { color: #8b5cf6; background: rgb(139 92 246 / .12); border-color: rgb(139 92 246 / .25); }
    .vc-badge-warning { color: var(--vc-warning); background: rgb(245 158 11 / .12); border-color: rgb(245 158 11 / .28); }
    .vc-badge-danger  { color: var(--vc-danger); background: rgb(239 68 68 / .12); border-color: rgb(239 68 68 / .25); }
    .vc-badge-neutral { color: var(--vc-muted); background: var(--vc-inset); border-color: var(--vc-border); }

    /* toolbar + search */
    .vc-toolbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .vc-search { position: relative; width: 100%; max-width: 18rem; }
    .vc-search .vc-ico { position: absolute; left: .7rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: var(--vc-faint); pointer-events: none; }
    .vc-input {
        width: 100%; font-size: .82rem; color: var(--vc-text);
        background: var(--vc-surface-2); border: 1px solid var(--vc-border);
        border-radius: .65rem; padding: .5rem .75rem; outline: none; transition: box-shadow .15s, border-color .15s;
    }
    .vc-input::placeholder { color: var(--vc-faint); }
    .vc-input:focus { border-color: var(--vc-accent); box-shadow: 0 0 0 3px var(--vc-accent-soft); }
    .vc-search .vc-input { padding-left: 2.1rem; }

    /* category chips */
    .vc-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--vc-border); }
    .vc-chip {
        padding: .35rem .8rem; font-size: .78rem; font-weight: 500; border-radius: .55rem; cursor: pointer;
        background: transparent; color: var(--vc-muted); border: 1px solid transparent; transition: all .15s;
    }
    .vc-chip:hover { background: var(--vc-inset); color: var(--vc-text); }
    .vc-chip-active { background: var(--vc-accent); color: #fff; }
    .vc-chip-active:hover { background: var(--vc-accent-strong); color: #fff; }

    /* catalog groups + cards */
    .vc-group-label { font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--vc-faint); margin: 0 0 .6rem .15rem; }
    .vc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: .75rem; align-items: stretch; }
    .vc-card {
        display: flex; align-items: stretch; gap: .8rem; padding: .9rem; text-align: left; width: 100%;
        background: var(--vc-surface-2); border: 1px solid var(--vc-border); border-radius: var(--vc-radius);
        cursor: pointer; transition: border-color .15s, background-color .15s, transform .06s, box-shadow .15s;
    }
    .vc-card:hover { border-color: var(--vc-accent); box-shadow: 0 1px 0 var(--vc-accent-ring); }
    .vc-card:active { transform: translateY(1px); }
    .vc-card-icon { width: 2.6rem; height: 2.6rem; border-radius: .6rem; object-fit: contain; padding: .3rem; background: var(--vc-inset); flex-shrink: 0; align-self: flex-start; }
    .vc-card-fallback {
        width: 2.6rem; height: 2.6rem; border-radius: .6rem; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: .85rem; flex-shrink: 0; align-self: flex-start;
        background: var(--vc-accent-soft); color: var(--vc-accent-text);
    }
    .vc-card-body { min-width: 0; flex: 1 1 auto; display: flex; flex-direction: column; }
    .vc-card-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .vc-card-name { font-weight: 700; font-size: .88rem; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .vc-card-cta { display: inline-flex; align-items: center; gap: .25rem; font-size: .7rem; font-weight: 700; color: var(--vc-accent-text); flex-shrink: 0; }
    .vc-card-cta .vc-ico { width: .85rem; height: .85rem; }
    .vc-card-desc {
        font-size: .76rem; color: var(--vc-muted); line-height: 1.4; margin: .3rem 0 0;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .vc-card-builds { margin-top: auto; padding-top: .65rem; font-size: .68rem; color: var(--vc-faint); }
    .vc-empty { text-align: center; color: var(--vc-muted); font-size: .85rem; padding: 2rem 0; }

    /* progress */
    .vc-live { display: flex; align-items: center; gap: .6rem; }
    .vc-pulse { position: relative; width: .7rem; height: .7rem; flex-shrink: 0; }
    .vc-pulse span { position: absolute; inset: 0; border-radius: 50%; background: var(--vc-accent); }
    .vc-pulse span:first-child { animation: vc-ping 1.3s cubic-bezier(0,0,.2,1) infinite; opacity: .75; }
    @keyframes vc-ping { 75%, 100% { transform: scale(2.4); opacity: 0; } }
    .vc-term {
        margin-top: .9rem; height: 12.5rem; overflow-y: auto; white-space: pre-wrap; word-break: break-word;
        border-radius: .7rem; padding: .9rem 1rem; font-size: .74rem; line-height: 1.55;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        background: #0b0f16; color: #e5e7eb; border: 1px solid rgba(255,255,255,.06);
    }
    .vc-term-lg { height: 18rem; }

    /* result banner */
    .vc-result { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-radius: var(--vc-radius); padding: .9rem 1.1rem; border: 1px solid var(--vc-border); border-left-width: 4px; }
    .vc-result-done { border-left-color: var(--vc-success); background: rgb(16 185 129 / .07); }
    .vc-result-fail { border-left-color: var(--vc-danger); background: rgb(239 68 68 / .07); }
    .vc-result .vc-ico { width: 1.4rem; height: 1.4rem; flex-shrink: 0; }
    .vc-result-done .vc-ico { color: var(--vc-success); }
    .vc-result-fail .vc-ico { color: var(--vc-danger); }

    /* table */
    .vc-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
    .vc-table th { text-align: left; padding: .5rem .75rem; font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--vc-faint); border-bottom: 1px solid var(--vc-border); }
    .vc-table td { padding: .7rem .75rem; border-bottom: 1px solid var(--vc-border); }
    .vc-table tr:last-child td { border-bottom: none; }
    .vc-table tr:hover td { background: var(--vc-surface-2); }
    .vc-table-scroll { overflow-x: auto; }

    /* buttons */
    .vc-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
        padding: .55rem 1.1rem; font-size: .8rem; font-weight: 600; border-radius: .6rem; cursor: pointer;
        border: 1px solid transparent; transition: all .15s; line-height: 1.1; white-space: nowrap;
    }
    .vc-btn .vc-ico { width: 1rem; height: 1rem; }
    .vc-btn-primary { background: var(--vc-accent); color: #fff; }
    .vc-btn-primary:hover { background: var(--vc-accent-strong); }
    .vc-btn-primary:disabled { opacity: .5; cursor: not-allowed; }
    .vc-btn-ghost { background: transparent; color: var(--vc-text); border-color: var(--vc-border-2); }
    .vc-btn-ghost:hover { background: var(--vc-inset); }
    .vc-btn-xs { padding: .3rem .6rem; font-size: .72rem; border-radius: .45rem; }

    /* modal */
    .vc-overlay {
        position: fixed; inset: 0; z-index: 99990; display: flex; align-items: center; justify-content: center;
        padding: 1rem; background: rgb(0 0 0 / .6); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);
    }
    .vc-modal {
        width: 100%; max-width: 34rem; max-height: 90vh; display: flex; flex-direction: column;
        background: var(--vc-surface); border: 1px solid var(--vc-border); border-radius: 1rem;
        box-shadow: 0 25px 60px -15px rgb(0 0 0 / .5);
    }
    .dark .vc-modal { background: #151b26; }
    .vc-modal-lg { max-width: 44rem; }
    .vc-modal-head { display: flex; align-items: flex-start; gap: .85rem; padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--vc-border); }
    .vc-modal-head-icon { width: 2.4rem; height: 2.4rem; border-radius: .6rem; object-fit: contain; padding: .3rem; background: var(--vc-inset); flex-shrink: 0; }
    .vc-modal-title { font-size: 1.02rem; font-weight: 700; margin: 0; letter-spacing: -.01em; }
    .vc-modal-desc { font-size: .78rem; color: var(--vc-muted); margin: .15rem 0 0; }
    .vc-modal-x { margin-left: auto; cursor: pointer; color: var(--vc-faint); background: none; border: none; padding: .2rem; line-height: 0; border-radius: .4rem; }
    .vc-modal-x:hover { color: var(--vc-text); background: var(--vc-inset); }
    .vc-modal-x svg { width: 1.15rem; height: 1.15rem; }
    .vc-modal-body { padding: 1.25rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1.05rem; }
    .vc-modal-foot { display: flex; justify-content: flex-end; gap: .6rem; padding: 1rem 1.25rem; border-top: 1px solid var(--vc-border); }

    .vc-field-label { display: block; font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--vc-muted); margin-bottom: .45rem; }
    .vc-select {
        width: 100%; font-size: .82rem; font-weight: 500; color: var(--vc-text);
        background: var(--vc-surface-2); border: 1px solid var(--vc-border); border-radius: .6rem; padding: .6rem .75rem; outline: none;
        appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right .6rem center; background-size: 1.1rem; padding-right: 2.2rem;
    }
    .vc-select:focus { border-color: var(--vc-accent); box-shadow: 0 0 0 3px var(--vc-accent-soft); }
    /* Native dropdown popup follows the panel theme (dark-rendered options, readable text). */
    .dark .vc-select, .dark .vc-input { color-scheme: dark; }
    .dark .vc-select option { background-color: #1b2230; color: #f3f4f6; }
    .dark .vc-select option:checked { background-color: var(--vc-accent); color: #fff; }
    .vc-chip-row { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .55rem; }

    .vc-note { border-radius: .6rem; border: 1px solid var(--vc-border); background: var(--vc-surface-2); padding: .8rem .9rem; font-size: .76rem; color: var(--vc-muted); }
    .vc-note-row { display: flex; align-items: center; gap: .5rem; }
    .vc-note-row + .vc-note-row { margin-top: .45rem; }
    .vc-note-row .vc-ico { width: 1rem; height: 1rem; flex-shrink: 0; }
    .vc-note code { font-size: .72rem; padding: .05rem .3rem; border-radius: .3rem; background: var(--vc-inset); }

    .vc-warn { display: flex; gap: .55rem; border-radius: .6rem; padding: .75rem .85rem; font-size: .76rem; border: 1px solid rgb(245 158 11 / .35); background: rgb(245 158 11 / .1); color: var(--vc-text); }
    .vc-warn .vc-ico { width: 1rem; height: 1rem; color: var(--vc-warning); flex-shrink: 0; margin-top: .05rem; }

    .vc-check { display: flex; gap: .65rem; align-items: flex-start; border-radius: .6rem; border: 1px solid var(--vc-border); background: var(--vc-surface-2); padding: .8rem .9rem; cursor: pointer; transition: border-color .15s, background-color .15s; }
    .vc-check-active { border-color: rgb(239 68 68 / .45); background: rgb(239 68 68 / .07); }
    .vc-check input { margin-top: .15rem; width: 1rem; height: 1rem; flex-shrink: 0; accent-color: var(--vc-danger); cursor: pointer; }
    .vc-check-title { font-size: .8rem; font-weight: 600; }
    .vc-check-active .vc-check-title { color: var(--vc-danger); }
    .vc-check-desc { font-size: .74rem; color: var(--vc-muted); margin: .15rem 0 0; }
    .vc-danger-note { margin-top: .6rem; border-radius: .45rem; border: 1px solid rgb(239 68 68 / .4); background: rgb(239 68 68 / .1); color: var(--vc-danger); padding: .55rem .65rem; font-size: .73rem; font-weight: 500; }

    .vc-changelog { border-radius: .6rem; border: 1px solid var(--vc-border); background: var(--vc-surface-2); padding: .7rem .85rem; margin-top: .55rem; font-size: .74rem; color: var(--vc-muted); }
    .vc-changelog ul { margin: .35rem 0 0; padding-left: 1.1rem; }
    .vc-changelog li { margin: .15rem 0; }

    .vc-spin { animation: vc-spin 1s linear infinite; }
    @keyframes vc-spin { to { transform: rotate(360deg); } }
    .vc-loading { display: flex; align-items: center; gap: .5rem; font-size: .78rem; color: var(--vc-muted); padding: .4rem 0; }
    .vc-loading .vc-ico { width: 1rem; height: 1rem; color: var(--vc-accent); }
</style>

<div class="vc" @if($isChanging) wire:poll.2000ms="pollProgress" @endif>

    {{-- Running server notice --}}
    @if($this->isServerRunning() && !$isChanging)
        <div class="vc-banner">
            <svg class="vc-ico" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            <div><b>Server is online ({{ ucfirst($containerStatus) }}).</b> It is stopped gracefully before any file is replaced — a kill signal is never sent.</div>
        </div>
    @endif

    {{-- Active change --}}
    @if($isChanging || in_array($changeStatus, ['pending', 'changing'], true))
        <div class="vc-panel">
            <div class="vc-live">
                <span class="vc-pulse"><span></span><span></span></span>
                <div>
                    <h3 class="vc-panel-title">Switching server version…</h3>
                    <p class="vc-panel-sub">Status: <span style="color:var(--vc-accent-text);font-weight:600;text-transform:uppercase;">{{ $changeStatus }}</span> · operation #{{ $changeId }}</p>
                </div>
            </div>
            <pre class="vc-term">{{ $changeLog ?: 'Connecting to the Wings daemon...' }}</pre>
        </div>
    @elseif(in_array($changeStatus, ['done', 'failed'], true))
        <div class="vc-result {{ $changeStatus === 'done' ? 'vc-result-done' : 'vc-result-fail' }}">
            <div style="display:flex;align-items:center;gap:.75rem;">
                @if($changeStatus === 'done')
                    <svg class="vc-ico" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <div>
                        <div style="font-weight:600;font-size:.88rem;">Version change complete</div>
                        <div class="vc-panel-sub">The server software has been replaced. See the log for details.</div>
                    </div>
                @else
                    <svg class="vc-ico" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <div>
                        <div style="font-weight:600;font-size:.88rem;">Version change failed</div>
                        <div class="vc-panel-sub" style="color:var(--vc-danger);">{{ $changeError ?: 'An unexpected error occurred.' }}</div>
                    </div>
                @endif
            </div>
            <button type="button" class="vc-btn vc-btn-ghost vc-btn-xs" wire:click="$set('changeStatus', '')">Dismiss</button>
        </div>
    @endif

    {{-- Current installed version --}}
    @if($currentVersionInfo)
        <div class="vc-panel">
            <div class="vc-hero">
                @php
                    $installedIcon = $currentVersionInfo['icon'] ?? null;
                    if (!$installedIcon && !empty($currentVersionInfo['software'])) {
                        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $currentVersionInfo['software']));
                        $installedIcon = "https://s3.mcjars.app/icons/{$slug}.png";
                    }
                @endphp
                @if(!empty($installedIcon))
                    <img src="{{ $installedIcon }}" alt="" class="vc-hero-icon" onerror="this.onerror=null;this.style.display='none';">
                @endif
                <div>
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.45rem;">
                        <span class="vc-hero-label">Current installed version</span>
                        <span class="vc-badge vc-badge-success"><span class="vc-dot"></span>Active</span>
                        @if(($currentVersionInfo['source'] ?? '') === 'modpack')
                            <span class="vc-badge vc-badge-info">Modpack</span>
                        @elseif(($currentVersionInfo['source'] ?? '') === 'disk')
                            <span class="vc-badge vc-badge-accent">Auto-detected</span>
                        @endif
                    </div>
                    <h3 class="vc-hero-title">{{ $currentVersionInfo['software'] }} <span>{{ $currentVersionInfo['version'] }}</span>
                        @if(!empty($currentVersionInfo['build']))
                            <span style="font-size:.8rem;font-family:ui-monospace,monospace;color:var(--vc-faint);font-weight:500;">({{ $currentVersionInfo['build'] }})</span>
                        @endif
                    </h3>
                </div>
                @if(!empty($currentVersionInfo['installed_at']))
                    <div class="vc-hero-meta">
                        <div class="vc-hero-label">Installed</div>
                        <div style="font-size:.8rem;font-weight:500;margin-top:.15rem;">{{ $currentVersionInfo['installed_at'] }}</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Catalog --}}
    <div class="vc-panel">
        <div class="vc-toolbar">
            <div>
                <div class="vc-panel-head">
                    <svg class="vc-ico" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                    <h2 class="vc-panel-title">Software catalog</h2>
                </div>
                <p class="vc-panel-sub">Pick a server software to switch to. Your current JAR is backed up first.</p>
            </div>
            <div class="vc-search">
                <svg class="vc-ico" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="search" class="vc-input" wire:model.live.debounce.300ms="search" placeholder="Filter software (e.g. Paper)...">
            </div>
        </div>

        @php
            $categories = [
                'recommended'   => 'Recommended',
                'all'           => 'All',
                'established'   => 'Established',
                'experimental'  => 'Experimental',
                'miscellaneous' => 'Miscellaneous',
                'limbos'        => 'Limbos',
            ];
        @endphp
        <div class="vc-chips">
            @foreach($categories as $catKey => $catLabel)
                <button wire:key="cat-{{ $catKey }}" type="button" wire:click="selectCategory('{{ $catKey }}')"
                    class="vc-chip {{ $selectedCategory === $catKey ? 'vc-chip-active' : '' }}">{{ $catLabel }}</button>
            @endforeach
        </div>
    </div>

    @php $filteredGroup = $this->filteredTypes; @endphp
    @if(empty($filteredGroup))
        <div class="vc-panel"><div class="vc-empty">No software types found matching your query.</div></div>
    @else
        @foreach($filteredGroup as $catName => $softwares)
            <div wire:key="cat-group-{{ $catName }}">
                <div class="vc-group-label">{{ ucfirst($catName) }}</div>
                <div class="vc-grid">
                    @foreach($softwares as $key => $software)
                        <button wire:key="sw-{{ $key }}" type="button" wire:click="openSoftwareModal('{{ $key }}')" wire:loading.attr="disabled" class="vc-card">
                            @if(!empty($software['icon']))
                                <img src="{{ $software['icon'] }}" alt="" loading="lazy" class="vc-card-icon" onerror="this.onerror=null;this.style.visibility='hidden';">
                            @else
                                <div class="vc-card-fallback">{{ strtoupper(substr($software['name'] ?? $key, 0, 2)) }}</div>
                            @endif
                            <div class="vc-card-body">
                                <div class="vc-card-top">
                                    <span class="vc-card-name">{{ $software['name'] }}</span>
                                    <span class="vc-card-cta">Install
                                        <svg class="vc-ico" fill="none" viewBox="0 0 24 24" stroke-width="2.3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                    </span>
                                </div>
                                <p class="vc-card-desc">{{ $software['description'] ?? 'Minecraft server software.' }}</p>
                                @if(!empty($software['builds']))
                                    <div class="vc-card-builds">{{ number_format($software['builds']) }} builds available</div>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

    {{-- History --}}
    @if(!empty($recentChanges))
        <div class="vc-panel">
            <div class="vc-panel-head" style="margin-bottom:.9rem;">
                <svg class="vc-ico" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <h3 class="vc-panel-title">Recent version changes</h3>
            </div>
            <div class="vc-table-scroll">
                <table class="vc-table">
                    <thead>
                        <tr><th>Software &amp; version</th><th>Build</th><th>Status</th><th>Date</th><th style="text-align:right;">Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentChanges as $change)
                            <tr wire:key="hist-{{ $change['id'] }}">
                                <td style="font-weight:600;">{{ $change['software'] }} {{ $change['minecraft_version'] }}</td>
                                <td class="vc-muted">{{ $change['build_name'] ?: ('#' . ($change['build_number'] ?? 'latest')) }}</td>
                                <td>
                                    @if($change['status'] === 'done')
                                        <span class="vc-badge vc-badge-success">Completed</span>
                                    @elseif($change['status'] === 'failed')
                                        <span class="vc-badge vc-badge-danger">Failed</span>
                                    @else
                                        <span class="vc-badge vc-badge-warning">{{ ucfirst($change['status']) }}</span>
                                    @endif
                                </td>
                                <td class="vc-muted">{{ !empty($change['created_at']) ? substr($change['created_at'], 0, 19) : '' }}</td>
                                <td style="text-align:right;">
                                    <button type="button" class="vc-btn vc-btn-ghost vc-btn-xs" wire:click="openLogModal({{ $change['id'] }})">View log</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ============ INSTALL MODAL ============ --}}
    @if($showInstallModal && $selectedSoftware)
        <div class="vc-overlay" wire:click.self="closeInstallModal" x-data @keydown.escape.window="$wire.closeInstallModal()">
            <div class="vc-modal">
                <div class="vc-modal-head">
                    @if(!empty($softwareDetails['icon']))
                        <img src="{{ $softwareDetails['icon'] }}" alt="" class="vc-modal-head-icon">
                    @endif
                    <div style="min-width:0;">
                        <h3 class="vc-modal-title">Install {{ $softwareDetails['name'] ?? $selectedSoftware }}</h3>
                        <p class="vc-modal-desc">{{ $softwareDetails['description'] ?? 'Configure the version and build for your server.' }}</p>
                    </div>
                    <button type="button" class="vc-modal-x" wire:click="closeInstallModal" aria-label="Close">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="vc-modal-body">
                    {{-- version --}}
                    <div>
                        <label class="vc-field-label">1. Minecraft version</label>
                        @if($isLoadingVersions)
                            <div class="vc-loading"><svg class="vc-ico vc-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/><path fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z"/></svg> Loading available versions...</div>
                        @elseif(empty($availableVersions))
                            <p class="vc-panel-sub">No versions available for this software.</p>
                        @else
                            @php
                                $vSearch = strtolower(trim($versionSearch));
                                $filteredVersions = empty($vSearch) ? $availableVersions
                                    : array_filter($availableVersions, fn($k) => str_contains(strtolower((string) $k), $vSearch), ARRAY_FILTER_USE_KEY);
                            @endphp
                            <div class="vc-search" style="max-width:none;margin-bottom:.5rem;">
                                <svg class="vc-ico" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                                <input type="search" class="vc-input" wire:model.live.debounce.200ms="versionSearch" placeholder="Filter versions... (e.g. 1.20.4)">
                            </div>
                            @if(empty($filteredVersions))
                                <p class="vc-panel-sub">No versions match “{{ $versionSearch }}”.</p>
                            @else
                                <select class="vc-select" wire:change="selectVersion($event.target.value)">
                                    @foreach($filteredVersions as $vKey => $vData)
                                        <option value="{{ $vKey }}" @selected($selectedVersion === $vKey)>Minecraft {{ $vKey }}@if(!empty($vData['type']) && $vData['type'] !== 'RELEASE') ({{ $vData['type'] }})@endif</option>
                                    @endforeach
                                </select>
                            @endif
                            @if($versionDetails)
                                <div class="vc-chip-row">
                                    @if(isset($versionDetails['supported']))
                                        <span class="vc-badge {{ $versionDetails['supported'] ? 'vc-badge-success' : 'vc-badge-neutral' }}">{{ $versionDetails['supported'] ? 'Stable release' : 'Snapshot / legacy' }}</span>
                                    @endif
                                    @if(isset($versionDetails['java']))
                                        <span class="vc-badge vc-badge-info">Java {{ $versionDetails['java'] }}</span>
                                    @endif
                                    @if(isset($versionDetails['builds']))
                                        <span class="vc-badge vc-badge-neutral">{{ number_format($versionDetails['builds']) }} builds</span>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- build --}}
                    <div>
                        <label class="vc-field-label">2. JAR build</label>
                        @if($isLoadingBuilds)
                            <div class="vc-loading"><svg class="vc-ico vc-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/><path fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z"/></svg> Loading builds...</div>
                        @else
                            <select class="vc-select" wire:change="selectBuild($event.target.value)">
                                <option value="latest" @selected($selectedBuildNumber === 'latest' || empty($selectedBuildNumber))>Latest build (recommended)</option>
                                @foreach($availableBuilds as $b)
                                    @php
                                        $isSpecial = in_array(strtoupper($selectedSoftware ?? ''), ['FABRIC','FORGE','NEOFORGE','SPONGE','LEGACYFABRIC','QUILT'], true);
                                        $val = $isSpecial && !empty($b['name']) ? $b['name'] : (string) ($b['buildNumber'] ?? $b['name'] ?? '');
                                    @endphp
                                    <option value="{{ $val }}" @selected((string) $selectedBuildNumber === (string) $val)>Build {{ $b['name'] ?? ('#' . ($b['buildNumber'] ?? '')) }}@if(!empty($b['created'])) ({{ substr($b['created'], 0, 10) }})@endif</option>
                                @endforeach
                            </select>
                            @if(!empty($selectedBuild['changes']))
                                <div class="vc-changelog">
                                    <span style="font-weight:600;color:var(--vc-text);">Changelog</span>
                                    <ul>@foreach(array_slice($selectedBuild['changes'], 0, 3) as $c)<li>{{ $c }}</li>@endforeach</ul>
                                </div>
                            @endif
                        @endif
                    </div>

                    @if(!empty($javaWarning))
                        <div class="vc-warn">
                            <svg class="vc-ico" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                            <div>
                                <div style="font-weight:600;">Java compatibility warning</div>
                                Minecraft {{ $javaWarning['version'] }} requires <b>Java {{ $javaWarning['required'] }}</b>, but your server image uses <b>Java {{ $javaWarning['detected'] }}</b>. Update the Docker image before starting.
                            </div>
                        </div>
                    @endif

                    <div class="vc-note">
                        <div class="vc-note-row"><svg class="vc-ico" style="color:var(--vc-success);" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg> Creates a safety backup (<code>server.jar.bak</code>)</div>
                        <div class="vc-note-row"><svg class="vc-ico" style="color:var(--vc-success);" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg> Preserves the Minecraft EULA (<code>eula.txt</code>)</div>
                        <div class="vc-note-row"><svg class="vc-ico" style="color:var(--vc-accent-text);" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9"/></svg> Stops the server gracefully first — never a kill signal</div>
                        @if(!empty($selectedBuild['jarSize']))
                            <div class="vc-note-row"><svg class="vc-ico" style="color:var(--vc-accent-text);" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg> Download size: ~{{ round($selectedBuild['jarSize'] / (1024 * 1024), 1) }} MB</div>
                        @endif
                    </div>

                    <label class="vc-check {{ $cleanInstall ? 'vc-check-active' : '' }}">
                        <input type="checkbox" wire:model.live="cleanInstall">
                        <div>
                            <div class="vc-check-title">Clean install — delete all server files before installing</div>
                            <p class="vc-check-desc">Useful when migrating between ecosystems (e.g. Vanilla → Forge). Unchecked by default.</p>
                            @if($cleanInstall)
                                <div class="vc-danger-note">Irreversible: all worlds, plugins, mods and configs are deleted first. Only <code>server.jar.bak</code> and <code>eula.txt</code> are kept.</div>
                            @endif
                        </div>
                    </label>

                    @if($this->isServerRunning())
                        <div class="vc-warn">
                            <svg class="vc-ico" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                            <div><b>Server is online.</b> It is stopped gracefully (no kill) before any file is touched, then restarted on the new version.</div>
                        </div>
                    @endif
                </div>

                <div class="vc-modal-foot">
                    <button type="button" class="vc-btn vc-btn-ghost" wire:click="closeInstallModal">Cancel</button>
                    <button type="button" class="vc-btn vc-btn-primary" wire:click="startVersionChange" wire:target="startVersionChange" wire:loading.attr="disabled" @disabled($isChanging || empty($selectedSoftware) || empty($selectedVersion))>
                        <svg class="vc-ico" wire:loading.remove wire:target="startVersionChange" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        <svg class="vc-ico vc-spin" wire:loading wire:target="startVersionChange" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".25"/><path fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z"/></svg>
                        <span wire:loading.remove wire:target="startVersionChange">Install</span>
                        <span wire:loading wire:target="startVersionChange">Starting…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ LOG MODAL ============ --}}
    @if($showLogModal && $viewingRecord)
        <div class="vc-overlay" wire:click.self="closeLogModal" x-data @keydown.escape.window="$wire.closeLogModal()">
            <div class="vc-modal vc-modal-lg">
                <div class="vc-modal-head">
                    <div style="min-width:0;">
                        <h3 class="vc-modal-title">Execution log #{{ $viewingRecord['id'] }}</h3>
                        <p class="vc-modal-desc">{{ $viewingRecord['software'] }} {{ $viewingRecord['minecraft_version'] }} ({{ $viewingRecord['status'] }})</p>
                    </div>
                    <button type="button" class="vc-modal-x" wire:click="closeLogModal" aria-label="Close">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="vc-modal-body">
                    <pre class="vc-term vc-term-lg" style="margin-top:0;">{{ $viewingRecord['log'] ?: 'No log recorded.' }}</pre>
                </div>
                <div class="vc-modal-foot">
                    <button type="button" class="vc-btn vc-btn-ghost" wire:click="closeLogModal">Close</button>
                </div>
            </div>
        </div>
    @endif

</div>
</x-filament-panels::page>
