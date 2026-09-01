/**
 * SidebarComponent — state management for the responsive sidebar.
 *
 * The sidebar has three distinct visual states:
 *
 *   Desktop (>= 1024px)
 *     - `mini`  : icon-only rail (64px). Default for returning desktop users.
 *     - `expanded`: full panel (256px). Toggled via the hamburger button.
 *                   Hovering the mini rail expands it temporarily ("peek").
 *   Mobile / Tablet (< 1024px)
 *     - The sidebar is rendered as an off-canvas drawer with a translucent
 *       backdrop. `open` controls both the drawer slide-in and the backdrop.
 *
 * RTL / LTR
 *     Sliding is driven entirely by logical CSS properties (`inset-inline-start`,
 *     `padding-inline-start`, `border-inline-end`, `ms-auto`/`me-auto`), so the
 *     drawer naturally slides from the right in Arabic (dir="rtl") and from the
 *     left in English (dir="ltr") without any direction branching in this module.
 *     The current direction is read from `document.documentElement.dir` for aria
 *     labels / a11y strings only (visual layout needs no JS logic).
 *
 * All state is persisted to localStorage so the preference survives reloads.
 */

/** localStorage keys. */
const SIDEBAR_STORAGE_KEY = 'zkpm_sidebar';
const SIDEBAR_GROUP_PREFIX = 'zkpm_sidebar_group_';

/** Breakpoint (px) matching Tailwind's `lg` (1024px). */
const DESKTOP_BREAKPOINT = 1024;

/** Toggle the CSS variation class emitted for host pages (optional styling hook). */
let doc = () => document.documentElement;

/**
 * Return true when the current viewport is desktop-sized.
 *
 * @returns {boolean} Whether the layout is a desktop (>= 1024px) rail.
 */
export function isDesktop() {
    return window.innerWidth >= DESKTOP_BREAKPOINT;
}

/**
 * Return the active document direction.
 *
 * @returns {('rtl'|'ltr')} Document text direction.
 */
export function currentDirection() {
    return doc().dir === 'rtl' ? 'rtl' : 'ltr';
}

/**
 * Build and register the global `sidebar` Alpine store.
 *
 * Must be called before Alpine.start().
 *
 * @param {import('alpinejs').Alpine} Alpine - The Alpine instance.
 * @returns {void}
 */
export function registerSidebarStore(Alpine) {
    Alpine.store('sidebar', {
        /** Drawer visibility (mobile/tablet off-canvas + backdrop). */
        open: false,

        /** Desktop icon-rail collapsed state (persisted). */
        mini: localStorage.getItem(SIDEBAR_STORAGE_KEY) === 'mini',

        /** Collapsible accordion groups (persisted per group). */
        groups: {
            main: localStorage.getItem(SIDEBAR_GROUP_PREFIX + 'main') !== 'collapsed',
            security: localStorage.getItem(SIDEBAR_GROUP_PREFIX + 'security') !== 'collapsed',
        },

        /** True while the user is hovering the desktop mini rail (peek). */
        peeking: false,

        /** True while a mobile drawer transition is running. */
        drawerVisible: false,

        /** Reactive viewport flag (>= 1024px). Kept in sync on resize. */
        desktop: window.innerWidth >= DESKTOP_BREAKPOINT,

        /**
         * Ensure the `desktop` flag tracks the current viewport width.
         * Called automatically on resize.
         *
         * @returns {void}
         */
        syncViewport() {
            this.desktop = window.innerWidth >= DESKTOP_BREAKPOINT;
        },

        /**
         * Toggle the account-level sidebar state:
         *   - Desktop: flips between expanded rail and mini rail.
         *   - Mobile : flips the off-canvas drawer.
         *
         * @returns {void}
         */
        toggle() {
            if (isDesktop()) {
                this.mini = !this.mini;
                localStorage.setItem(SIDEBAR_STORAGE_KEY, this.mini ? 'mini' : 'expanded');
            } else {
                this.open = !this.open;
                this.drawerVisible = this.open;
                this.lockBodyScroll(this.open);
            }
        },

        /**
         * Open the mobile drawer. No-op on desktop.
         *
         * @returns {void}
         */
        openDrawer() {
            if (isDesktop()) return;
            this.open = true;
            this.drawerVisible = true;
            this.lockBodyScroll(true);
        },

        /**
         * Close the mobile drawer (Esc / backdrop / nav click). No-op on desktop.
         *
         * @returns {void}
         */
        closeDrawer() {
            if (isDesktop()) return;
            this.open = false;
            this.drawerVisible = false;
            this.lockBodyScroll(false);
        },

        /**
         * Expand the desktop rail when the mouse enters the mini rail.
         *
         * @returns {void}
         */
        peekStart() {
            if (this.mini) this.peeking = true;
        },

        /**
         * Collapse the desktop rail back to mini on mouse leave.
         *
         * @returns {void}
         */
        peekEnd() {
            this.peeking = false;
        },

        /**
         * Whether the desktop sidebar should be drawn as expanded.
         * True when expanded by default OR temporarily while peeking the rail.
         *
         * @returns {boolean}
         */
        get expanded() {
            return !this.mini || this.peeking;
        },

        /**
         * Toggle an accordion group (e.g. 'main' | 'security').
         *
         * @param {string} name - Group identifier.
         * @returns {void}
         */
        toggleGroup(name) {
            this.groups[name] = !this.groups[name];
            localStorage.setItem(
                SIDEBAR_GROUP_PREFIX + name,
                this.groups[name] ? 'expanded' : 'collapsed'
            );
        },

        /**
         * Whether a navigation group is currently open.
         *
         * @param {string} name - Group identifier.
         * @returns {boolean}
         */
        isGroupOpen(name) {
            return this.groups[name];
        },

        /**
         * Lock / unlock page scroll while the mobile drawer is open.
         *
         * @param {boolean} lock - True to prevent body scroll.
         * @returns {void}
         */
        lockBodyScroll(lock) {
            document.body.style.overflow = lock ? 'hidden' : '';
        },

        /**
         * Close the drawer on the Escape key (mobile/tablet only).
         *
         * @returns {void}
         */
        onKeydown(event) {
            if (event.key !== 'Escape') return;
            if (this.open && !isDesktop()) {
                this.closeDrawer();
                event.preventDefault();
            }
        },
    });

    // Global key handler so Esc closes the drawer even when focus is outside
    // Alpine-managed nodes.
    doc().addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const store = Alpine.store('sidebar');
            if (store.open && !isDesktop()) {
                store.closeDrawer();
            }
        }
    });

    // When resizing across the lg breakpoint, close any lingering drawer and
    // stop peeking so the desktop rail renders from a clean state.
    window.addEventListener('resize', () => {
        const store = Alpine.store('sidebar');
        store.syncViewport();
        store.peeking = false;
        store.drawerVisible = false;
        if (isDesktop()) {
            store.open = false;
            store.lockBodyScroll(false);
        }
    });
}
