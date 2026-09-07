/**
 * Theme, language, and toast management for ZeroKnowledgePM.
 *
 * - Theme: `data-theme="dark" | "light"` on <html>, kept in sync with
 *   Tailwind's `.dark` class, persisted in localStorage (`zkpm_theme`,
 *   default DARK — the Vault Console default). The no-flicker bootstrap
 *   lives inline in the layout <head>; this module exposes the runtime
 *   toggle API.
 * - Language: `zkpm_lang` persisted in a cookie (read server-side by
 *   SetLocale middleware for Blade __() strings) and localStorage; a page
 *   reload re-renders the server strings and flips dir=rtl for Arabic.
 *   Arabic is the application default; English is the fallback.
 * - Toasts: an Alpine store that any module (or plain JS) can push to via
 *   window.zkpmToast() or a `zkpm:toast` CustomEvent.
 */

/** localStorage keys. */
export const THEME_STORAGE_KEY = 'zkpm_theme';
export const LANG_STORAGE_KEY = 'zkpm_lang';

/** Supported UI locales (mirrors SetLocale middleware). */
const SUPPORTED_LANGS = ['en', 'ar'];

/**
 * Resolve the active theme: persisted preference if set, otherwise the
 * product default (dark). Matches the head bootstrap fallback so the
 * toggle shows the correct state on the very first visit before the user
 * has chosen anything. Dark always wins for unsaved visitors — light only
 * ever comes from an explicit stored choice or an in-app toggle.
 *
 * @returns {string} 'dark' or 'light'.
 */
export function currentTheme() {
    const saved = localStorage.getItem(THEME_STORAGE_KEY);
    return saved === 'light' ? 'light' : 'dark';
}

/**
 * Apply a theme to the document root: sets the `data-theme` attribute (the
 * system that drives the Vault Console palette) and mirrors it onto
 * Tailwind's `.dark` class so every `dark:` utility resolves.
 *
 * @param {string} theme - 'dark' or 'light'.
 * @returns {void}
 */
export function applyTheme(theme) {
    const root = document.documentElement;
    root.setAttribute('data-theme', theme);
    root.classList.toggle('dark', theme === 'dark');
    localStorage.setItem(THEME_STORAGE_KEY, theme);
}

/**
 * Switch themes with a single compositor-assisted cross-fade.
 *
 * Wraps the DOM mutation in the View Transitions API when the browser
 * supports it and the user has not opted into reduced motion; otherwise
 * it applies instantly. This avoids the per-element color transitions
 * that previously re-painted the whole page at once and caused lag on
 * dense screens (dashboard, vault, audit logs).
 *
 * @param {string} theme - 'dark' or 'light'.
 * @returns {void}
 */
export function setTheme(theme) {
    if (theme !== 'dark' && theme !== 'light' || theme === currentTheme()) {
        return;
    }

    const apply = () => {
        applyTheme(theme);
        window.dispatchEvent(new CustomEvent('zkpm:theme-changed', { detail: { theme } }));
    };

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (typeof document.startViewTransition === 'function' && !prefersReducedMotion) {
        document.startViewTransition(apply);
    } else {
        apply();
    }
}

/**
 * Toggle between light and dark themes.
 *
 * @returns {string} The newly applied theme.
 */
export function toggleTheme() {
    const next = currentTheme() === 'dark' ? 'light' : 'dark';
    setTheme(next);
    return next;
}

/**
 * Read the persisted UI language.
 *
 * @returns {string} 'ar' or 'en'.
 */
export function currentLanguage() {
    const lang = localStorage.getItem(LANG_STORAGE_KEY);
    return SUPPORTED_LANGS.includes(lang) ? lang : 'ar';
}

/**
 * Persist and activate a UI language, then reload so server-rendered
 * strings and the document direction update.
 *
 * @param {string} lang - Target locale ('en' or 'ar').
 * @returns {void}
 */
export function setLanguage(lang) {
    if (!SUPPORTED_LANGS.includes(lang)) return;

    document.cookie = `${LANG_STORAGE_KEY}=${lang}; path=/; max-age=31536000; SameSite=Lax`;
    localStorage.setItem(LANG_STORAGE_KEY, lang);
    window.location.reload();
}

/**
 * Register the global toast store on the Alpine instance.
 *
 * Must be called before Alpine.start().
 *
 * @param {import('alpinejs').Alpine} Alpine - The Alpine instance.
 * @returns {void}
 */
export function registerToastStore(Alpine) {
    Alpine.store('toasts', {
        items: [],
        sequence: 0,

        /**
         * Push a toast onto the stack.
         *
         * @param {string} message - Display text.
         * @param {string} type - 'success' | 'error' | 'info' | 'warning'.
         * @param {number} duration - Auto-dismiss delay in ms.
         * @returns {number} The toast id.
         */
        push(message, type = 'info', duration = 3500) {
            const id = ++this.sequence;
            this.items.push({ id, message, type, duration });
            setTimeout(() => this.dismiss(id), duration);
            return id;
        },

        /**
         * Remove a toast by id.
         *
         * @param {number} id - Toast identifier.
         * @returns {void}
         */
        dismiss(id) {
            this.items = this.items.filter((toast) => toast.id !== id);
        },
    });

    // Plain-JS bridge: dispatch `zkpm:toast` from anywhere without Alpine.
    window.addEventListener('zkpm:toast', (event) => {
        const { message, type } = event.detail ?? {};
        Alpine.store('toasts').push(message ?? '', type ?? 'info');
    });

    window.zkpmToast = (message, type = 'info') => {
        Alpine.store('toasts').push(message, type);
    };
}

/**
 * Wire the language switcher buttons (progressive enhancement). The theme
 * toggle is fully handled by Alpine in the `theme-switcher` Blade component,
 * so it is intentionally NOT bound here to avoid double-toggling.
 *
 * @returns {void}
 */
export function initThemeSwitchers() {
    document.querySelectorAll('[data-set-lang]').forEach((btn) => {
        btn.addEventListener('click', () => {
            setLanguage(btn.dataset.setLang);
        });
    });
}
