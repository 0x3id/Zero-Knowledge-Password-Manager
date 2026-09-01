@php
    // ── Primary navigation (collapsible "Main" accordion group) ────────────
    $navMain = [
        [
            'label' => __('Dashboard'),
            'route' => 'dashboard',
            'active' => request()->routeIs('dashboard'),
            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        ],
        [
            'label' => __('Vault'),
            'route' => 'vault',
            'active' => request()->routeIs('vault'),
            'iconComponent' => 'vault',
        ],
        [
            'label' => __('Categories'),
            'route' => 'categories.index',
            'active' => request()->routeIs('categories.*'),
            'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        ],
        [
            'label' => __('Password Generator'),
            'route' => 'generator',
            'active' => request()->routeIs('generator'),
            'iconComponent' => 'generator',
        ],
    ];

    // ── Security navigation (collapsible "Security" accordion group) ───────
    $navSecurity = [
        [
            'label' => __('Security Center'),
            'route' => 'settings',
            'active' => request()->routeIs('settings'),
            'iconComponent' => 'security',
        ],
        [
            'label' => __('Activity Audit Log'),
            'route' => 'audit-logs.index',
            'active' => request()->routeIs('audit-logs.*'),
            'iconComponent' => 'audit-log',
        ],
        [
            'label' => __('Active Sessions'),
            'route' => 'sessions.index',
            'active' => request()->routeIs('sessions.*'),
            'iconComponent' => 'devices',
        ],
        [
            'label' => __('Passkeys'),
            'route' => 'webauthn.index',
            'active' => request()->routeIs('webauthn.*'),
            'iconComponent' => 'webauthn',
        ],
    ];

    $userInitial = strtoupper(substr(auth()->user()->name ?? 'U', 0, 1));
@endphp

{{-- Shared sidebar body — rendered inside BOTH the desktop rail and the mobile
     drawer. Text/collapse behaviour follows the Alpine `sidebar` store:
     mini rails hide labels via utility classes driven by `$store.sidebar.mini`. --}}
<div class="flex h-full min-h-0 flex-col overflow-x-hidden overflow-y-auto scrollbar-cyber">

    {{-- ══ Brand (hidden in mini rail: only icon shown) ─────────────────── --}}
    <div class="sidebar-brand flex h-14 shrink-0 items-center border-b border-[var(--vc-border)] px-5 lg:h-16">
        <a href="{{ route('dashboard') }}" class="group flex items-center gap-3">
            <span class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl shadow-glow-sm transition group-hover:scale-105 group-hover:shadow-glow group-active:scale-95">
                <x-brand-mark class="h-9 w-9 rounded-xl" />
                <span aria-hidden="true" class="absolute -bottom-0.5 -end-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-cyber-black"></span>
            </span>
            <span class="sidebar-text flex flex-col leading-none">
                <span class="text-base font-bold tracking-tight text-[var(--vc-text)]">
                    ZeroKnowledge<span class="text-[var(--vc-accent)]">PM</span>
                </span>
                <span class="mt-1 text-[9px] uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                    {{ __('Zero-Knowledge Vault') }}
                </span>
            </span>
        </a>
    </div>

    {{-- ══ Quick actions (labels hidden in mini rail) ───────────────────── --}}
    <div class="shrink-0 border-b border-[var(--vc-border)] px-3 py-3">
        <div class="sidebar-quick-row flex items-center gap-2">
            <a href="{{ route('vault') }}"
               title="{{ __('Add Item') }}"
               class="sidebar-quick group glass-btn flex flex-1 items-center justify-center gap-2 px-3 py-2 text-[13px] font-semibold text-[var(--vc-accent)] transition-all duration-200 active:scale-[0.97]">
                <x-icon-plus class="h-4 w-4 shrink-0" />
                <span class="sidebar-text">{{ __('Add Item') }}</span>
            </a>
            <a href="{{ route('generator') }}"
               title="{{ __('Generate') }}"
               class="sidebar-quick group glass-btn flex flex-1 items-center justify-center gap-2 px-3 py-2 text-[13px] font-semibold text-[var(--vc-accent)] transition-all duration-200 active:scale-[0.97]">
                <x-icon-generator class="h-4 w-4 shrink-0" />
                <span class="sidebar-text">{{ __('Generate') }}</span>
            </a>
        </div>
    </div>

    {{-- ══ Navigation (accordion groups) ─────────────────────────────────── --}}
    <nav class="sidebar-nav flex-1 space-y-1 px-3 py-3" aria-label="{{ __('Main navigation') }}">

        {{-- ── Main group ── --}}
        <div x-data="{ open: $store.sidebar.isGroupOpen('main') }">
            <button type="button"
                    @click="open = !open; $store.sidebar.toggleGroup('main')"
                    class="sidebar-group flex w-full items-center gap-2 rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-[var(--vc-text-dim)] transition-colors duration-200 hover:bg-[var(--vc-surface-2)] hover:text-[var(--vc-text)]">
                <span class="sidebar-text flex-1 text-start">{{ __('Main') }}</span>
                <x-icon-chevron-down class="sidebar-chevron h-3 w-3 shrink-0 transition-transform duration-300" x-bind:class="open ? 'rotate-180' : ''" />
            </button>
            <div x-show="open"
                 x-collapse.duration.250ms
                 x-cloak>
                <ul class="mt-0.5 space-y-0.5">
                    @foreach ($navMain as $item)
                        <li>
                            <a href="{{ route($item['route']) }}"
                               title="{{ $item['label'] }}"
                               class="sidebar-item group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold transition-all duration-200 {{ $item['active'] ? 'glass-active text-[var(--vc-accent)]' : 'text-[var(--vc-text-dim)] hover:bg-[var(--vc-surface-2)] hover:text-[var(--vc-text)] active:scale-[0.98]' }}">
                                @if ($item['active'])
                                    <span class="absolute start-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-full bg-[var(--vc-accent)] shadow-[0_0_10px_rgba(62,207,142,0.6)]"></span>
                                @endif
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border text-[var(--vc-accent)] transition {{ $item['active'] ? 'glass-active' : 'border-[var(--vc-border)] bg-[var(--vc-surface)] text-[var(--vc-text-dim)] group-hover:border-[color:var(--vc-accent)]/50 group-hover:text-[var(--vc-accent)]' }}">
                                    @if (!empty($item['iconComponent']))
                                        @php($iconComponent = 'icons.'.$item['iconComponent'])
                                        <x-dynamic-component :component="$iconComponent" class="h-4 w-4" />
                                    @else
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                                    @endif
                                </span>
                                <span class="sidebar-text">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- ── Security group ── --}}
        <div x-data="{ open: $store.sidebar.isGroupOpen('security') }">
            <button type="button"
                    @click="open = !open; $store.sidebar.toggleGroup('security')"
                    class="sidebar-group flex w-full items-center gap-2 rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-[var(--vc-text-dim)] transition-colors duration-200 hover:bg-[var(--vc-surface-2)] hover:text-[var(--vc-text)]">
                <span class="sidebar-text flex-1 text-start">{{ __('Security') }}</span>
                <x-icon-chevron-down class="sidebar-chevron h-3 w-3 shrink-0 transition-transform duration-300" x-bind:class="open ? 'rotate-180' : ''" />
            </button>
            <div x-show="open"
                 x-collapse.duration.250ms
                 x-cloak>
                <ul class="mt-0.5 space-y-0.5">
                    @foreach ($navSecurity as $item)
                        <li>
                            <a href="{{ route($item['route']) }}"
                               title="{{ $item['label'] }}"
                               class="sidebar-item group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-semibold transition-all duration-200 {{ $item['active'] ? 'glass-active text-[var(--vc-accent)]' : 'text-[var(--vc-text-dim)] hover:bg-[var(--vc-surface-2)] hover:text-[var(--vc-text)] active:scale-[0.98]' }}">
                                @if ($item['active'])
                                    <span class="absolute start-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-full bg-[var(--vc-accent)] shadow-[0_0_10px_rgba(62,207,142,0.6)]"></span>
                                @endif
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border text-[var(--vc-accent)] transition {{ $item['active'] ? 'glass-active' : 'border-[var(--vc-border)] bg-[var(--vc-surface)] text-[var(--vc-text-dim)] group-hover:border-[color:var(--vc-accent)]/50 group-hover:text-[var(--vc-accent)]' }}">
                                    @if (!empty($item['iconComponent']))
                                        @php($iconComponent = 'icons.'.$item['iconComponent'])
                                        <x-dynamic-component :component="$iconComponent" class="h-4 w-4" />
                                    @else
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                                    @endif
                                </span>
                                <span class="sidebar-text">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </nav>

    {{-- ══ Footer: user profile + controls ───────────────────────────────── --}}
    <div class="shrink-0 border-t border-[var(--vc-border)]">

        {{-- User profile --}}
        <a href="{{ route('profile.edit') }}"
           title="{{ auth()->user()->name }}"
           class="sidebar-user group flex items-center gap-3 px-4 py-3 text-xs text-[var(--vc-text-dim)] transition hover:bg-[var(--vc-surface-2)] hover:text-[var(--vc-text)]">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-[var(--vc-accent)] to-[color-mix(in_srgb,var(--vc-accent)_55%,var(--vc-bg))] text-[10px] font-bold uppercase text-[var(--vc-bg)] transition-shadow group-hover:shadow-glow-sm">
                {{ $userInitial }}
            </span>
            <span class="sidebar-text min-w-0 flex-1">
                <span class="block truncate text-[13px] font-semibold text-slate-700 dark:text-slate-200">{{ auth()->user()->name }}</span>
                <span class="block truncate text-[11px] text-slate-400 dark:text-slate-500">{{ auth()->user()->email }}</span>
            </span>
            <x-icon-chevron-right class="sidebar-text h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-[var(--vc-accent)] text-[var(--vc-text-dim)] rtl:scale-x-[-1]" />
        </a>

        {{-- Auto-lock countdown widget (Vault Console) --}}
        <div id="auto-lock-widget"
             class="auto-lock-widget mx-3 mb-2"
             role="status"
             aria-live="polite">
            <div class="flex items-center justify-between gap-2">
                <span class="vc-eyebrow inline-flex items-center gap-1.5">
                    <span data-autolock-icon class="relative inline-flex text-[var(--vc-accent)]">
                        <span data-lock-ico class="inline-flex"><x-icon-lock class="h-3 w-3" /></span>
                        <span data-unlock-ico class="hidden"><x-icon-unlock class="h-3 w-3" /></span>
                    </span>
                    {{ __('Auto-lock') }}
                </span>
                <span data-autolock-time class="vc-mono text-[11px] font-medium tabular-nums">30:00</span>
            </div>
            <div class="auto-lock-track mt-1.5">
                <div data-autolock-fill class="auto-lock-fill" style="width: 100%;"></div>
            </div>
            <span data-autolock-state class="vc-locked-state vc-mono mt-1 block text-[9px] font-medium tracking-widest uppercase" data-locked-label="{{ __('Locked') }}"></span>
        </div>

        {{-- Controls + version --}}
        <div class="sidebar-controls flex items-center gap-2 border-t border-[var(--vc-border)] px-4 py-3">
            {{-- <x-theme-switcher /> --}}
            {{-- <x-language-switcher /> --}}
            <form method="POST" action="{{ route('logout') }}" class="ms-auto">
                @csrf
                <button type="submit"
                        title="{{ __('Log Out') }}"
                        aria-label="{{ __('Log Out') }}"
                        class="glass-btn inline-flex h-9 w-9 items-center justify-center text-slate-400 transition duration-200 ease-in-out hover:-translate-y-px hover:text-red-600 hover:border-red-400/70 active:translate-y-0 active:scale-95 dark:text-slate-500 dark:hover:border-red-400/50 dark:hover:text-red-400">
                     <x-icon-logout class="h-4 w-4" />
                </button>
            </form>
        </div>

        <p class="sidebar-text px-4 pb-3 text-[10px] font-medium uppercase tracking-widest text-[var(--vc-text-dim)]">
            {{ config('app.name', 'ZeroKnowledgePM') }} · v1.0
        </p>
    </div>
</div>
