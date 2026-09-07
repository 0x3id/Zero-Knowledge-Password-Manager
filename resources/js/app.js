import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { initAuth } from './auth.js';
import { initConsole } from './console.js';
import { initLanding } from './landing.js';
import { initLock } from './lock.js';
import { initThemeSwitchers, registerToastStore, setTheme } from './theme.js';
import { registerSidebarStore } from './sidebar.js';
import { initWebauthnManager } from './webauthn-manager.js';

window.Alpine = Alpine;

// Expose the theme toggle to the Alpine theme-switcher component: it
// routes through `setTheme()` so switching uses a single compositor
// cross-fade (View Transitions API) instead of per-element transitions.
window.zkpmSetTheme = setTheme;

// Global plugins & stores must exist before Alpine boots the DOM.
Alpine.plugin(collapse);
registerToastStore(Alpine);
registerSidebarStore(Alpine);

Alpine.start();

// Initialize all zero-knowledge application modules
document.addEventListener('DOMContentLoaded', () => {
    initThemeSwitchers();
    initConsole();
    initLanding();
    initAuth();
    initLock();
    initWebauthnManager();

    // Route-level code splitting: the heavy page-specific modules are only
    // downloaded when their containers exist on the current page, keeping the
    // initial payload small for landing/auth/dashboard/settings pages.
    if (document.getElementById('vault-item-modal')) {
        import('./vault.js').then(({ initVault }) => initVault());
    }
    if (document.getElementById('generator-app')) {
        import('./generator.js').then(({ initGenerator }) => initGenerator());
    }
});
