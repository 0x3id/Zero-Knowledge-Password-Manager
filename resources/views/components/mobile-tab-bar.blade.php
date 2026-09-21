{{-- Mobile bottom tab bar — the PRIMARY navigation pattern on phones/tablets
     (< lg, i.e. < 1025px). Runs the full width, respects the home-indicator
     safe area, and always keeps the auto-lock countdown + lock action in
     reach (they live in the persistent topbar chip). The desktop left-rail
     sidebar replaces this bar from `min-width: 1025px` upward. --}}
<nav class="mobile-tab-bar lg:hidden" aria-label="{{ __('Main navigation') }}">

    <a href="{{ route('dashboard') }}"
       aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}"
       class="{{ request()->routeIs('dashboard') ? 'active-tab' : '' }}">
        <x-icon-dashboard class="h-6 w-6" />
    </a>

    <a href="{{ route('vault') }}"
       aria-current="{{ request()->routeIs('vault') ? 'page' : 'false' }}"
       class="{{ request()->routeIs('vault') ? 'active-tab' : '' }}">
        <x-icon-vault class="h-6 w-6" />
    </a>

    <a href="{{ route('generator') }}"
       aria-current="{{ request()->routeIs('generator') ? 'page' : 'false' }}"
       class="{{ request()->routeIs('generator') ? 'active-tab' : '' }}">
        <x-icon-generator class="h-6 w-6" />
    </a>

    <a href="{{ route('settings') }}"
       aria-current="{{ request()->routeIs('settings') ? 'page' : 'false' }}"
       class="{{ request()->routeIs('settings') ? 'active-tab' : '' }}">
        <x-icon-security class="h-6 w-6" />
    </a>

    <button type="button"
            @click="$store.sidebar.openDrawer()"
            aria-controls="mobile-drawer"
            aria-label="{{ __('More menu') }}"
            {{ request()->routeIs('categories.*', 'audit-logs.*', 'sessions.*', 'webauthn.*', 'profile.*') ? 'tab-active' : '' }}>
        <x-icon-menu class="h-6 w-6" />
    </button>
</nav>