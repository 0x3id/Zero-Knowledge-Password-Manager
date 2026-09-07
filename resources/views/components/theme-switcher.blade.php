{{--
    Theme switcher (Vault Console pill): two labelled options — Dark / Light.
    Sets the `data-theme` attribute on <html> (the palette driver) and keeps
    Tailwind's `.dark` class in sync. State persists in localStorage under
    `zkpm_theme` (default dark).
--}}
<div class="theme-pill"
     x-data="{ theme: document.documentElement.getAttribute('data-theme') || 'dark' }"
     @zkpm:theme-changed.window="theme = document.documentElement.getAttribute('data-theme') || 'dark'"
     role="group"
     aria-label="{{ __('Theme') }}">
    <button type="button"
            class="theme-pill-option"
            :aria-pressed="theme === 'dark'"
            @click="window.zkpmSetTheme('dark')"
            title="{{ __('Dark theme') }}"
            aria-label="{{ __('Dark theme') }}">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <span class="hidden sm:inline">{{ __('Dark') }}</span>
    </button>
    <button type="button"
            class="theme-pill-option"
            :aria-pressed="theme === 'light'"
            @click="window.zkpmSetTheme('light')"
            title="{{ __('Light theme') }}"
            aria-label="{{ __('Light theme') }}">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span class="hidden sm:inline">{{ __('Light') }}</span>
    </button>
</div>
