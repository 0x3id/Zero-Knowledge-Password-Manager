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
            @click="theme = 'dark'; document.documentElement.setAttribute('data-theme', 'dark'); document.documentElement.classList.add('dark'); localStorage.setItem('zkpm_theme', 'dark'); window.dispatchEvent(new CustomEvent('zkpm:theme-changed', { detail: { theme: 'dark' } }))"
            title="{{ __('Dark theme') }}"
            aria-label="{{ __('Dark theme') }}">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <span>{{ __('Dark') }}</span>
    </button>
    <button type="button"
            class="theme-pill-option"
            :aria-pressed="theme === 'light'"
            @click="theme = 'light'; document.documentElement.setAttribute('data-theme', 'light'); document.documentElement.classList.remove('dark'); localStorage.setItem('zkpm_theme', 'light'); window.dispatchEvent(new CustomEvent('zkpm:theme-changed', { detail: { theme: 'light' } }))"
            title="{{ __('Light theme') }}"
            aria-label="{{ __('Light theme') }}">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span>{{ __('Light') }}</span>
    </button>
</div>
