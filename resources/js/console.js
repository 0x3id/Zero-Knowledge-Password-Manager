/**
 * Vault Console live elements.
 *
 * 1. Live cipher strip: cycles a few example plaintext → ciphertext pairs
 *    every ~2.5s in JetBrains Mono, reinforcing that encryption happens
 *    client-side and in real time. Purely decorative client-side demo data —
 *    never real keys or credentials.
 * 2. Auto-lock countdown widget: a small persistent panel (sidebar footer)
 *    showing the time remaining before the vault auto-locks, with an accent
 *    progress bar mirroring the 30-minute inactivity timeout.
 */

/** Cipher strip: example plaintext → digest pairs (demo only). */
const CIPHER_PAIRS = [
    ['myAccount#42', '9f2a3e7c1b…'],
    ['mail@now.com', '7d18c0a5f4…'],
    ['wifi-hotel08', '3c6e91b2d8…'],
    ['shopping::2026', 'a41f7b9e03…'],
    ['gaming-!$star', '0b8d2f5a71…'],
];

/** Cipher strip frame duration in ms. */
const CIPHER_INTERVAL = 2500;

/** Auto-lock timeout in ms (must mirror lock.js AUTO_LOCK_TIMEOUT_MS). */
const AUTO_LOCK_TIMEOUT_MS = 30 * 60 * 1000;

/** Activity events that reset the inactivity auto-lock countdown. */
const ACTIVITY_EVENTS = ['pointerdown', 'keydown', 'mousemove', 'touchstart', 'scroll'];

/**
 * Start the live cipher strip cycling.
 *
 * @returns {void}
 */
function startCipherStrip() {
    const line = document.getElementById('cipher-strip-line');
    if (!line) return;

    let index = 0;

    const render = () => {
        const row = CIPHER_PAIRS[index % CIPHER_PAIRS.length];
        line.textContent = `${row[0]} → ${row[1]}`;
        index += 1;
    };

    render();
    setInterval(render, CIPHER_INTERVAL);
}

/**
 * Initialize the auto-lock countdown widget.
 *
 * The countdown mirrors the vault's 30-minute inactivity timeout: it resets
 * on real user activity and on vault unlock, and shows a "locked" idle state
 * when the vault is locked. The accent bar drains toward zero as the timeout
 * approaches.
 *
 * @returns {void}
 */
function initAutoLockWidget() {
    // The auto-lock state is rendered in two places that share a single
    // countdown: the sidebar widget (sidebar-content) and the compact mobile
    // chip in the topbar (navigation). Update every driven node so both stay
    // in perfect sync as the timeout runs down.
    const timeEls = document.querySelectorAll('[data-autolock-time]');
    if (timeEls.length === 0) return;

    const fillEl = document.querySelector('[data-autolock-fill]');
    const stateEl = document.querySelector('[data-autolock-state]');
    const icoEl = document.querySelector('[data-autolock-icon]');
    const lockIco = icoEl?.querySelector('[data-lock-ico]');
    const unlockIco = icoEl?.querySelector('[data-unlock-ico]');

    const lockedLabel = stateEl?.dataset.lockedLabel || 'Locked';

    const setIcon = (lockedNow) => {
        if (!icoEl || !lockIco || !unlockIco) return;
        lockIco.classList.toggle('hidden', lockedNow);
        unlockIco.classList.toggle('hidden', !lockedNow);
        icoEl.classList.toggle('text-[var(--vc-accent)]', !lockedNow);
        icoEl.classList.toggle('text-[var(--vc-warn)]', lockedNow);
    };

    let endAt = Date.now() + AUTO_LOCK_TIMEOUT_MS;
    let locked = false;

    const render = () => {
        const text = (() => {
            if (locked) return '—';
            const remaining = Math.max(0, endAt - Date.now());
            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000)
                .toString()
                .padStart(2, '0');
            return `${minutes}:${seconds}`;
        })();

        timeEls.forEach((el) => { el.textContent = text; });

        if (fillEl) {
            if (locked) {
                fillEl.style.width = '0%';
            } else {
                const remaining = Math.max(0, endAt - Date.now());
                fillEl.style.width = `${Math.round((remaining / AUTO_LOCK_TIMEOUT_MS) * 100)}%`;
            }
        }
    };

    const reset = () => {
        endAt = Date.now() + AUTO_LOCK_TIMEOUT_MS;
        locked = false;
        if (stateEl) stateEl.textContent = '';
        setIcon(false);
        render();
    };

    const lock = () => {
        locked = true;
        if (stateEl) stateEl.textContent = lockedLabel;
        setIcon(true);
        render();
    };

    ACTIVITY_EVENTS.forEach((eventName) => {
        document.addEventListener(eventName, reset, { passive: true });
    });

    window.addEventListener('zkpm:vault-unlocked', reset);
    window.addEventListener('zkpm:vault-locked', lock);

    reset();
    setInterval(render, 1000);
}

/**
 * Initialize Vault Console live elements.
 *
 * @returns {void}
 */
export function initConsole() {
    startCipherStrip();
    initAutoLockWidget();
}
