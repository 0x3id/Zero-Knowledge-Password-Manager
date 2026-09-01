/**
 * Vault auto-lock and unlock modal logic.
 *
 * Implements SRS FR-7:
 * - 30-minute inactivity timeout clearing in-memory CryptoKeys and IndexedDB unlock blobs.
 * - Non-dismissible re-authentication lock modal overlay.
 * - Dual unlock methods: Master Password (fallback with vault canary verification) and WebAuthn biometrics.
 * - Immediate manual lock trigger from UI.
 * - Seamless key handoff from 2FA login via ephemeral sessionStorage.
 */

import { ENCRYPTION_KEY_STORAGE_KEY } from './auth.js';
import {
    base64ToBytes,
    bytesToBase64,
    clearUnlockBlob,
    deriveEncryptionKey,
    deriveMasterKey,
    getEncryptionKey,
    lockVault,
    readUnlockBlob,
    saveUnlockBlob,
    unlockVault,
    verifyVaultCanary,
} from './crypto.js';
import { getWebauthnAssertion, webauthnErrorMessage } from './webauthn.js';

/** Inactivity timeout in milliseconds (30 minutes). */
const AUTO_LOCK_TIMEOUT_MS = 30 * 60 * 1000;

/** Module-scoped timer state. */
let lockTimer = null;
let vaultUnlocked = false;

/**
 * Read the CSRF token from the page meta tag.
 *
 * @returns {string} The CSRF token.
 */
function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

/**
 * Perform a POST returning JSON; throws on non-2xx responses.
 *
 * @param {string} url - Target URL.
 * @param {Object} payload - JSON body payload.
 * @returns {Promise<Object>} Parsed response data.
 * @throws {Error} On failure.
 */
async function postJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || window.zkpmT('Request failed.'));
    }

    return data;
}

/**
 * Show error inside the lock modal.
 *
 * @param {string} message - Error text.
 */
function showLockError(message) {
    const box = document.getElementById('lock-error');
    if (box) {
        box.textContent = message;
        box.classList.remove('hidden');
    }
}

/**
 * Hide the lock modal error box.
 */
function hideLockError() {
    const box = document.getElementById('lock-error');
    if (box) {
        box.classList.add('hidden');
    }
}

/**
 * Display the un-dismissible lock modal.
 */
function showLockModal() {
    const modal = document.getElementById('lock-modal');
    if (modal) {
        modal.classList.remove('hidden');
    }
    const input = document.getElementById('lock-unlock-password');
    if (input) input.focus();
}

/**
 * Hide the lock modal overlay.
 */
function hideLockModal() {
    const modal = document.getElementById('lock-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

/**
 * Reset the 30-minute inactivity timer.
 */
function resetLockTimer() {
    if (lockTimer !== null) {
        clearTimeout(lockTimer);
    }
    lockTimer = setTimeout(() => {
        performAutoLock();
    }, AUTO_LOCK_TIMEOUT_MS);
}

/**
 * Put the page into the locked UI state WITHOUT showing the modal popup:
 * hides the vault content and reveals the inline unlock form so the user
 * can unlock in place on page load. The lock modal overlay is reserved for
 * the inactivity auto-lock (and explicit manual lock).
 *
 * @returns {Promise<void>}
 */
function syncLockedUi() {
    lockVault();
    vaultUnlocked = false;

    const unlockedBox = document.getElementById('vault-content');
    if (unlockedBox) unlockedBox.classList.add('hidden');

    const unlockForm = document.getElementById('unlock-form');
    if (unlockForm) unlockForm.classList.remove('hidden');

    window.dispatchEvent(new CustomEvent('zkpm:vault-locked'));
}

/**
 * Perform auto-lock: clear key material from memory and IndexedDB, show lock modal.
 *
 * @returns {Promise<void>}
 */
export async function performAutoLock() {
    lockVault();
    sessionStorage.removeItem(ENCRYPTION_KEY_STORAGE_KEY);
    await clearUnlockBlob();
    vaultUnlocked = false;

    hideLockError();
    showLockModal();

    const unlockedBox = document.getElementById('vault-content');
    if (unlockedBox) unlockedBox.classList.add('hidden');

    const unlockForm = document.getElementById('unlock-form');
    if (unlockForm) unlockForm.classList.remove('hidden');

    window.dispatchEvent(new CustomEvent('zkpm:vault-locked'));
}

/**
 * Mark vault as unlocked and update UI views.
 */
function markUnlocked() {
    vaultUnlocked = true;
    hideLockModal();

    const unlockedBox = document.getElementById('vault-content');
    if (unlockedBox !== null) {
        unlockedBox.classList.remove('hidden');
    }

    const unlockForm = document.getElementById('unlock-form');
    if (unlockForm !== null) {
        unlockForm.classList.add('hidden');
    }

    resetLockTimer();
    window.dispatchEvent(new CustomEvent('zkpm:vault-unlocked'));
}

/**
 * Unlock vault locally using the Master Password.
 *
 * @param {string} password - Master password.
 * @param {string} email - Account email.
 * @returns {Promise<void>}
 */
async function unlockWithPassword(password, email) {
    const saltResponse = await fetch(`/kdf-salt?email=${encodeURIComponent(email)}`, {
        headers: { 'Accept': 'application/json' },
    });
    const saltData = await saltResponse.json().catch(() => ({}));
    const kdfSalt = saltData.kdf_salt;
    if (typeof kdfSalt !== 'string' || kdfSalt.length === 0) {
        throw new Error(window.zkpmT('Request failed.'));
    }

    const masterKey = await deriveMasterKey(password, kdfSalt);
    const encryptionKey = await deriveEncryptionKey(masterKey);

    const lockResponse = await fetch('/lock-data', {
        headers: { 'Accept': 'application/json' },
    });
    const lockData = await lockResponse.json().catch(() => ({}));
    if (typeof lockData.vault_canary !== 'string' || lockData.vault_canary.length === 0) {
        throw new Error(window.zkpmT('Request failed.'));
    }

    // Verify derived key against the vault canary before storing in memory
    const isValid = await verifyVaultCanary(encryptionKey, lockData.vault_canary);
    if (!isValid) {
        throw new Error(window.zkpmT('Incorrect master password.'));
    }

    await unlockVault(encryptionKey);

    // Persist the key across page navigation: Blade pages reload on every
    // route change, so the in-memory key alone would be lost and the vault
    // would re-lock on any interaction. sessionStorage survives navigation
    // within the tab and is stripped on lock/manual-lock/auto-lock.
    sessionStorage.setItem(ENCRYPTION_KEY_STORAGE_KEY, bytesToBase64(encryptionKey));

    // Save unlock blob for subsequent biometric unlock on this device
    if (lockData.has_webauthn) {
        try {
            await saveUnlockBlob(encryptionKey);
        } catch {
            // Storage unavailable; fallback remains functional
        }
    }

    markUnlocked();
}

/**
 * Unlock vault locally using WebAuthn biometrics / security key assertion.
 *
 * @returns {Promise<void>}
 */
async function unlockWithWebauthn() {
    const options = await postJson('/2fa/webauthn/options', {});
    const assertion = await getWebauthnAssertion(options.publicKey);
    await postJson('/2fa/webauthn/verify', assertion);

    const encryptionKey = await readUnlockBlob();
    if (encryptionKey === null) {
        throw new Error(window.zkpmT('Device biometric unlock session expired; enter Master Password.'));
    }

    await unlockVault(encryptionKey);
    sessionStorage.setItem(ENCRYPTION_KEY_STORAGE_KEY, bytesToBase64(encryptionKey));
    markUnlocked();
}

/**
 * Wire activity event listeners to reset inactivity auto-lock timer.
 */
function wireActivityListeners() {
    ['pointerdown', 'keydown', 'mousemove', 'touchstart', 'scroll'].forEach((eventName) => {
        document.addEventListener(eventName, resetLockTimer, { passive: true });
    });
}

/**
 * Wire lock controls and buttons.
 */
function wireLock() {
    // Biometric unlock buttons
    const webauthnBtns = document.querySelectorAll('.unlock-webauthn-btn');
    webauthnBtns.forEach((btn) => {
        readUnlockBlob().then((blob) => {
            if (blob !== null && window.PublicKeyCredential !== undefined) {
                btn.classList.remove('hidden');
            }
        });

        btn.addEventListener('click', async () => {
            hideLockError();
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = window.zkpmT('Authenticating…');

            try {
                await unlockWithWebauthn();
            } catch (error) {
                showLockError(webauthnErrorMessage(error));
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    });

    // Manual lock triggers (Navbar lock button, etc.)
    document.querySelectorAll('.trigger-lock-btn, #lock-now, #nav-lock-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            performAutoLock();
        });
    });

    wireActivityListeners();

    // Check if key is already in memory (or restored across navigation)
    getEncryptionKey().then((key) => {
        if (key === null) {
            // Locked on page load: show the inline unlock form, NOT the
            // modal popup — the overlay is reserved for the 30-minute
            // inactivity auto-lock and explicit manual lock.
            syncLockedUi();
        } else {
            vaultUnlocked = true;
            resetLockTimer();
        }
    });
}

/**
 * Initialize lock system, wire forms, and process session key handoff.
 */
export function initLock() {
    const email = document.querySelector('[data-user-email]')?.dataset.userEmail ?? '';

    // Wire unlock forms (Dashboard inline form and Lock Modal form)
    const forms = document.querySelectorAll('#unlock-form, #lock-unlock-form');
    forms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideLockError();

            const submitButton = form.querySelector('button[type="submit"]');
            const input = form.querySelector('input[type="password"]');
            if (!input || !submitButton) return;

            submitButton.disabled = true;
            submitButton.textContent = window.zkpmT('Verifying…');

            try {
                await unlockWithPassword(input.value, email);
                input.value = '';
            } catch (error) {
                showLockError(error instanceof Error ? error.message : window.zkpmT('Unlock failed.'));
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = window.zkpmT('Unlock Vault');
            }
        });
    });

    // Process key handoff from Factor 2 (TOTP / WebAuthn)
    const storedKey = sessionStorage.getItem(ENCRYPTION_KEY_STORAGE_KEY);
    if (storedKey !== null) {
        sessionStorage.removeItem(ENCRYPTION_KEY_STORAGE_KEY);
        (async () => {
            try {
                await unlockVault(base64ToBytes(storedKey));
                wireActivityListeners();
                markUnlocked();
            } catch {
                wireLock();
            }
        })();
        return;
    }

    wireLock();
}
