{{-- App header: hamburger toggle + topbar + responsive sidebar.
     RTL/LTR is handled with logical CSS properties (start/end, ms/me,
     inset-inline-start) so the drawer slides from the right in Arabic
     (dir="rtl") and from the left in English (dir="ltr") automatically. --}}
<nav aria-label="{{ __('Main navigation') }}">

    {{-- ═══ Desktop fixed sidebar (>= 1024px) ═════════════════════════════ ═══
         Renders as a full panel or a mini icon rail based on the `sidebar`
         store. Hovering the mini rail expands it temporarily (peek). --}}
    <aside :class="{
                'w-64': $store.sidebar.expanded,
                'w-16': !$store.sidebar.expanded,
            }"
           :data-collapsed="!$store.sidebar.expanded ? 'true' : 'false'"
           @mouseenter="$store.sidebar.peekStart()"
           @mouseleave="$store.sidebar.peekEnd()"
           class="sidebar-desktop fixed inset-y-0 start-0 z-30 hidden flex-col overflow-hidden border-e bg-[var(--vc-surface)] backdrop-blur-glass transition-[width] duration-300 ease-in-out border-[var(--vc-border)] lg:flex"
           id="app-sidebar"
           aria-label="{{ __('Sidebar') }}">
        @include('layouts.sidebar-content')
    </aside>

    {{-- ═══ Mobile drawer backdrop (< 1024px) ═══════════════════════════════ ═══
         Translucent scrim that closes the drawer on click. Uses `start-0`
         with `inset-inline` logical spacing for correct RTL side. --}}
    <div x-show="$store.sidebar.open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="$store.sidebar.closeDrawer()"
         class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm lg:hidden"
         aria-hidden="true"></div>

    {{-- ═══ Mobile/tablet off-canvas drawer (< 1024px) ═══════════════════════ ═══
         Slides in from the inline-start edge (right in RTL, left in LTR)
         over the translucent backdrop. Slide direction is handled by the
         custom `.drawer-slide` classes in app.css (direction-aware). --}}
    <aside x-show="$store.sidebar.open"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="opacity-0 drawer-slide-in"
           x-transition:enter-end="opacity-100 drawer-slide-zero"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="opacity-100 drawer-slide-zero"
           x-transition:leave-end="opacity-0 drawer-slide-out"
           class="fixed inset-y-0 start-0 z-50 flex w-72 flex-col overflow-hidden border-e bg-[var(--vc-surface)] backdrop-blur-glass border-[var(--vc-border)] lg:hidden"
           aria-label="{{ __('Sidebar') }}">
        @include('layouts.sidebar-content')
    </aside>

    {{-- ═══ Slim interactive topbar ══════════════════════════════════════════ ═══
         On desktop the topbar is offset past the fixed sidebar (lg:ps-16 /
         lg:ps-64) so the hamburger toggle is never hidden underneath the rail.
         The :class binding keeps the offset in sync as the rail expands /
         collapses, using logical padding-inline for correct RTL/LTR. --}}
    <header :class="$store.sidebar.expanded ? 'lg:ps-64' : 'lg:ps-16'"
            class="sticky top-0 z-20 border-b bg-[var(--vc-surface)] backdrop-blur-glass border-[var(--vc-border)] transition-[padding-inline-start] duration-300 ease-in-out [padding-top:env(safe-area-inset-top)]">
        <div class="flex h-14 items-center gap-2 px-4 sm:px-6 lg:h-16 lg:ps-0 lg:pe-4">

            {{-- Hamburger / collapse toggle (always visible on ALL screen sizes) --}}
            <button type="button"
                    id="nav-toggle-btn"
                    @click="$store.sidebar.toggle()"
                    class="glass-btn inline-flex h-10 w-10 shrink-0 items-center justify-center text-slate-500 transition duration-200 ease-in-out hover:-translate-y-px hover:text-[var(--vc-accent)] active:translate-y-0 active:scale-95 text-[var(--vc-text-dim)]"
                    :title="$store.sidebar.desktop ? ($store.sidebar.mini ? '{{ __('Expand sidebar') }}' : '{{ __('Collapse sidebar') }}') : '{{ __('Close menu') }}'"
                    :aria-controls="'app-sidebar'"
                    :aria-expanded="$store.sidebar.desktop ? !$store.sidebar.mini : $store.sidebar.open"
                    aria-label="{{ __('Open menu') }}">
                {{-- Hamburger — shown on mobile/tablet and on the expanded desktop rail --}}
                <x-icon-menu x-show="!$store.sidebar.desktop || !$store.sidebar.mini" x-cloak class="h-4 w-4" />
                {{-- Collapse chevron — shown on the collapsed (mini) desktop rail --}}
                <x-icon-chevron-right x-show="$store.sidebar.desktop && $store.sidebar.mini" x-cloak class="h-4 w-4 rtl:scale-x-[-1]" />
            </button>

            <a href="{{ route('dashboard') }}" class="group flex items-center gap-2.5 lg:hidden" aria-label="ZeroKnowledgePM">
                <span class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl shadow-glow-sm transition group-hover:scale-105 group-hover:shadow-glow group-active:scale-95">
                    <x-brand-mark class="h-9 w-9 rounded-xl" />
                    <span aria-hidden="true" class="absolute -bottom-0.5 -end-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-cyber-black"></span>
                </span>
                <span class="hidden flex-col leading-none sm:flex">
                    <span class="text-sm font-bold tracking-tight text-slate-900 dark:text-white">
                        ZeroKnowledge<span class="text-[var(--vc-accent)]">PM</span>
                    </span>
                    <span class="mt-1 text-[9px] uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                        {{ __('Zero-Knowledge Vault') }}
                    </span>
                </span>
            </a>

            {{-- Right cluster --}}
            <div class="ms-auto flex items-center gap-2">

                <button type="button"
                        id="nav-lock-btn"
                        title="{{ __('Lock Vault') }}"
                        class="trigger-lock-btn inline-flex h-9 items-center gap-1.5 rounded-lg border border-amber-300/50 bg-white/40 px-3 text-xs font-semibold text-amber-700 backdrop-blur-glass shadow-glass-sm transition duration-200 ease-in-out hover:-translate-y-px hover:bg-white/60 hover:border-amber-400/70 hover:shadow-glass active:translate-y-0 active:scale-95 dark:border-amber-400/25 dark:bg-white/[0.045] dark:text-amber-300 dark:hover:bg-white/[0.08] dark:hover:border-amber-300/50">
                    <x-icon-lock class="hidden h-3.5 w-3.5 sm:inline" />
                    <span>{{ __('Lock Vault') }}</span>
                </button>

                <x-theme-switcher />
                <x-language-switcher />

                <x-dropdown align="end" width="48">
                    <x-slot name="trigger">
                        <button class="glass-btn inline-flex items-center gap-2 p-1.5 pe-3 text-sm font-medium text-slate-600 transition duration-200 ease-in-out hover:-translate-y-px hover:text-[var(--vc-text)] active:translate-y-0 active:scale-95 text-[var(--vc-text-dim)]">
                            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-gradient-to-br from-[var(--vc-accent)] to-[color-mix(in_srgb,var(--vc-accent)_55%,var(--vc-bg))] text-[10px] font-bold uppercase text-[var(--vc-bg)]">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </span>
                            <span class="hidden max-w-[130px] truncate sm:inline">{{ auth()->user()->name }}</span>
                            <x-icon-chevron-down class="h-4 w-4 text-slate-400" />
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="border-b border-slate-100 px-4 py-2.5 text-xs text-slate-400 dark:border-slate-700">
                            {{ __('Signed in as') }}<br>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ auth()->user()->email }}</span>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile & Passkeys') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </header>

    {{-- ═══ Live cipher strip (Vault Console signature) ════════════════════ ═══
         A full-width bar directly under the topbar spanning the content area.
         Cycles example plaintext → ciphertext pairs in real time (see
         console.js) to show that encryption happens client-side. --}}
    <div :class="$store.sidebar.expanded ? 'lg:ps-64' : 'lg:ps-16'"
         class="cipher-strip hidden transition-[padding-inline-start] duration-300 ease-in-out sm:block">
        <div class="cipher-strip-inner">
            <span class="vc-live-dot shrink-0" aria-hidden="true"></span>
            <span class="vc-eyebrow shrink-0">{{ __('Client-side encryption') }}</span>
            <span id="cipher-strip-line" class="vc-mono text-[11px] whitespace-nowrap"></span>
        </div>
    </div>
</nav>
