/**
 * Auth page handlers: registration, login (factor 1), TOTP challenge,
 * mandatory TOTP enrollment, and recovery.
 *
 * Wire-up is driven by DOM presence so one module safely covers all
 * auth Blade views. All server interaction uses the web routes with the
 * CSRF token from the meta tag; no REST API layer is involved.
 *
 * Zero-knowledge flow:
 *   1. The client derives the master key and the auth hash locally.
 *   2. The derived Encryption Key survives factor 2 in sessionStorage
 *      (`zkpm_encryption_key`), where it is set on the login page and
 *      kept through the TOTP challenge redirect to the dashboard.
 *   3. The dashboard's lock.js restores it into memory and removes it
 *      from sessionStorage; the 30-minute auto-lock clears memory.
 */

import {
    bytesToBase64,
    createRecoveryBlob,
    createVaultCanary,
    decryptRecoveryBlob,
    deriveAuthHashInput,
    deriveEncryptionKey,
    deriveMasterKey,
    generateKdfSalt,
    generateRecoveryKey,
    unlockVault,
    verifyVaultCanary,
} from './crypto.js';

/** sessionStorage key holding the Encryption Key across the login flow. */
export const ENCRYPTION_KEY_STORAGE_KEY = 'zkpm_encryption_key';

/** sessionStorage key holding the once-shown recovery key. */
const RECOVERY_KEY_STORAGE_KEY = 'zkpm_recovery_key';

/**
 * Read the CSRF token from the page meta tag.
 *
 * @returns {string} The CSRF token.
 */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').content;
}

/**
 * Perform a fetch with JSON body and CSRF header; throws on non-2xx.
 *
 * @param {string} url - The web route.
 * @param {Object} payload - The JSON body.
 * @returns {Promise<Object>} The parsed JSON response.
 * @throws {Error} With the server-provided message on failure.
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
 * Show an inline error box.
 *
 * @param {string} id - The element id of the error container.
 * @param {string} message - The message to show.
 * @returns {void}
 */
function showError(id, message) {
    const box = document.getElementById(id);
    if (box !== null) {
        box.textContent = message;
        box.classList.remove('hidden');
    }
}

/**
 * Hide an inline error box.
 *
 * @param {string} id - The element id of the error container.
 * @returns {void}
 */
function hideError(id) {
    const box = document.getElementById(id);
    if (box !== null) {
        box.classList.add('hidden');
    }
}

/**
 * Strength-meter visual configuration per level.
 *
 * @type {Array<{bar: string, width: string}>}
 */
const STRENGTH_LEVELS = [
    { bar: 'bg-slate-300 dark:bg-slate-600', width: '0%' },
    { bar: 'bg-red-500', width: '20%' },
    { bar: 'bg-orange-500', width: '40%' },
    { bar: 'bg-amber-500', width: '60%' },
    { bar: 'bg-emerald-500', width: '80%' },
    { bar: 'bg-blue-500', width: '100%' },
];

/**
 * Estimate the bit-entropy of a password from its usable character pool.
 *
 * @param {string} password - Candidate plaintext password.
 * @returns {{bits: number, label: string, level: number}} Entropy metrics.
 */
function passwordMetrics(password) {
    if (!password || password.length === 0) {
        return { bits: 0, label: '', level: 0 };
    }

    const pool = new Set([...password]).size;
    const bits = Math.round(password.length * Math.log2(Math.max(1, pool)) * 10) / 10;

    if (bits >= 100) {
        return { bits, label: window.zkpmT('Very Strong'), level: 5 };
    }
    if (bits >= 70) {
        return { bits, label: window.zkpmT('Strong'), level: 4 };
    }
    if (bits >= 50) {
        return { bits, label: window.zkpmT('Fair'), level: 3 };
    }
    if (bits >= 35) {
        return { bits, label: window.zkpmT('Weak'), level: 2 };
    }
    return { bits, label: window.zkpmT('Very Weak'), level: 1 };
}

/**
 * Mark a submit button as busy/loading or idle.
 *
 * Buttons carrying a `[data-spinner]` + `[data-label]` pair get a spinning
 * icon while derivations run; plain buttons fall back to `textContent`.
 *
 * @param {HTMLButtonElement} button - The submit button.
 * @param {boolean} disabled - Whether the button is disabled.
 * @param {boolean} loading - Whether to show the spinner.
 * @param {string} label - The label to display.
 * @returns {void}
 */
function setButtonState(button, { disabled = false, loading = false, label = '' } = {}) {
    if (button === null) return;
    button.disabled = disabled;

    const spinner = button.querySelector('[data-spinner]');
    const labelEl = button.querySelector('[data-label]');
    if (spinner !== null) {
        spinner.classList.toggle('hidden', !loading);
    }
    if (labelEl !== null) {
        labelEl.textContent = label;
    } else if (label !== '') {
        button.textContent = label;
    }
}

/**
 * Toggle the live "valid" check icon next to an email/username input.
 *
 * @param {string} inputId - The input element id.
 * @param {string} iconId - The check icon element id.
 * @param {(value: string) => boolean} isValid - Validity predicate.
 * @returns {void}
 */
function initLiveCheck(inputId, iconId, isValid) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input === null || icon === null) return;

    input.addEventListener('input', () => {
        icon.classList.toggle('hidden', !isValid(input.value));
    });
    icon.classList.toggle('hidden', !isValid(input.value));
}

/**
 * Warn when Caps Lock is active while typing in a password field.
 *
 * The field opts in via `data-capslock` and names its hint element with
 * `data-capslock-hint`.
 *
 * @returns {void}
 */
function initCapsLockHints() {
    document.querySelectorAll('[data-capslock]').forEach((input) => {
        const panel = document.getElementById(input.dataset.capslockHint);
        if (panel === null) return;

        const reveal = (event) => {
            const isOn = event.getModifierState('CapsLock');
            panel.classList.toggle('hidden', !isOn);
            panel.classList.toggle('flex', isOn);
        };

        input.addEventListener('keydown', reveal);
        input.addEventListener('keyup', reveal);
        input.addEventListener('blur', () => {
            panel.classList.add('hidden');
            panel.classList.remove('flex');
        });
    });
}

/**
 * Generate a cryptographically random password covering all four
 * character classes and safely shuffle the result.
 *
 * @param {number} length - Desired password length.
 * @returns {string} The generated password.
 */
function generateStrongPassword(length = 20) {
    const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const lower = 'abcdefghijklmnopqrstuvwxyz';
    const digits = '0123456789';
    const symbols = '!@#$%^&*()-_=+[]{};:,.<>?';
    const all = upper + lower + digits + symbols;

    const rand = (max) => {
        const bytes = new Uint32Array(1);
        crypto.getRandomValues(bytes);
        return Math.floor((bytes[0] / 2 ** 32) * max);
    };

    const chars = [upper, lower, digits, symbols].map((set) => set[rand(set.length)]);
    for (let i = chars.length; i < length; i += 1) {
        chars.push(all[rand(all.length)]);
    }
    for (let i = chars.length - 1; i > 0; i -= 1) {
        const j = rand(i + 1);
        [chars[i], chars[j]] = [chars[j], chars[i]];
    }
    return chars.join('');
}

/**
 * Live password strength meter + length hint on the registration form.
 *
 * @returns {void}
 */
function initRegisterStrengthMeter() {
    const input = document.getElementById('master-password');
    const bar = document.getElementById('register-strength-bar');
    const label = document.getElementById('register-strength-label');
    const note = document.getElementById('register-length-hint');
    if (input === null || bar === null || label === null) return;

    const update = () => {
        const metrics = passwordMetrics(input.value);
        const level = STRENGTH_LEVELS[metrics.level];

        bar.className = `zkpm-strength-bar-fill ${level.bar}`;
        bar.style.width = level.width;

        if (metrics.level === 0) {
            label.textContent = window.zkpmT('Password strength');
            label.className = 'whitespace-nowrap text-[10px] font-semibold text-slate-400 dark:text-slate-500';
        } else {
            label.textContent = `${metrics.label} — ${metrics.bits} bits`;
            label.className = 'whitespace-nowrap text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300';
        }

        if (note !== null) {
            const tooShort = input.value.length > 0 && input.value.length < 12;
            note.textContent = tooShort ? window.zkpmT('Min 12 characters') : '';
            note.className = `mt-1 text-[10px] min-h-[14px] ${tooShort ? 'font-semibold text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500'}`;
        }
    };

    input.addEventListener('input', update);
    update();
}

/**
 * Live match/no-match indicator for the confirmation password field.
 *
 * @returns {void}
 */
function initPasswordMatch() {
    const password = document.getElementById('master-password');
    const confirmation = document.getElementById('master-password-confirm');
    const indicator = document.getElementById('register-match');
    if (password === null || confirmation === null || indicator === null) return;

    const check = () => {
        indicator.classList.remove('hidden', 'flex', 'text-emerald-600', 'dark:text-emerald-400', 'text-red-600', 'dark:text-red-400');
        const value = confirmation.value;

        if (value.length === 0) {
            return;
        }
        indicator.classList.add('flex');
        if (value === password.value) {
            indicator.classList.add('text-emerald-600', 'dark:text-emerald-400');
            indicator.innerHTML = `✓ ${window.zkpmT('Passwords match')}`;
        } else {
            indicator.classList.add('text-red-600', 'dark:text-red-400');
            indicator.innerHTML = `✗ ${window.zkpmT('Passwords do not match.')}`;
        }
    };

    password.addEventListener('input', check);
    confirmation.addEventListener('input', check);
}

/**
 * "Generate Strong" button: fills the master password + confirmation with
 * a fresh random password and refreshes the strength meter/match state.
 *
 * @returns {void}
 */
function initRegisterGenerator() {
    const button = document.getElementById('register-generate-btn');
    const password = document.getElementById('master-password');
    const confirmation = document.getElementById('master-password-confirm');
    if (button === null || password === null || confirmation === null) return;

    button.addEventListener('click', () => {
        const generated = generateStrongPassword(20);
        password.value = generated;
        confirmation.value = generated;
        confirmation.dispatchEvent(new Event('input'));
    });
}

/**
 * Persist the derived Encryption Key in sessionStorage.
 *
 * The key lives in sessionStorage only for the factor-2 handoff: it is
 * written on the login page and consumed (moved into memory) by the
 * dashboard's lock.js after a successful TOTP verification.
 *
 * @param {Uint8Array} encryptionKeyBytes - The derived encryption key.
 * @returns {void}
 */
function persistEncryptionKey(encryptionKeyBytes) {
    sessionStorage.setItem(ENCRYPTION_KEY_STORAGE_KEY, bytesToBase64(encryptionKeyBytes));
}

/**
 * Guard and submit the registration form after client-side derivation.
 *
 * Step 1: derive master key and both purpose keys; generate the recovery
 * key; encrypt the encryption key into the recovery blob; create the
 * vault canary. Step 2: feed hidden fields and submit. The recovery key
 * is kept in sessionStorage only long enough for the "shown once"
 * display on the TOTP setup page, then removed.
 *
 * @returns {void}
 */
function initRegistration() {
    const form = document.getElementById('register-form');
    if (form === null) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError('register-error');

        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('master-password').value;
        const confirmation = document.getElementById('master-password-confirm').value;

        if (password !== confirmation) {
            showError('register-error', window.zkpmT('Passwords do not match.'));
            return;
        }

        const submitButton = document.getElementById('register-submit');
        setButtonState(submitButton, {
            disabled: true,
            loading: true,
            label: window.zkpmT('Deriving keys…'),
        });

        try {
            const salt = generateKdfSalt();
            const masterKey = await deriveMasterKey(password, salt);
            const encryptionKey = await deriveEncryptionKey(masterKey);
            const authHashInput = await deriveAuthHashInput(masterKey);

            const recoveryKey = generateRecoveryKey();
            const recoveryBlob = await createRecoveryBlob(recoveryKey, encryptionKey);
            const vaultCanary = await createVaultCanary(encryptionKey);

            document.getElementById('auth-hash-input').value = authHashInput;
            document.getElementById('kdf-salt').value = salt;
            document.getElementById('kdf-params').value = JSON.stringify({
                algorithm: 'PBKDF2-SHA256',
                iterations: 600000,
                hash: 'SHA-256',
            });
            document.getElementById('encrypted-recovery-blob').value = recoveryBlob;
            document.getElementById('vault-canary').value = vaultCanary;

            // Shown exactly once on the TOTP setup page, then removed.
            sessionStorage.setItem(RECOVERY_KEY_STORAGE_KEY, recoveryKey);

            form.submit();
        } catch (error) {
            showError('register-error', error instanceof Error ? error.message : window.zkpmT('Registration failed.'));
            setButtonState(submitButton, { disabled: false, label: window.zkpmT('Register') });
        }
    });
}

/**
 * Guard and submit the login form (factor 1).
 *
 * Derives the master key, fetches the user's KDF salt (or a decoy),
 * computes the auth hash input, and submits. The derived Encryption Key
 * is stashed in sessionStorage so the TOTP challenge can hand it to the
 * dashboard after successful verification.
 *
 * @returns {void}
 */
function initLogin() {
    const form = document.getElementById('login-form');
    if (form === null) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError('login-error');

        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('master-password').value;

        const submitButton = document.getElementById('login-submit');
        setButtonState(submitButton, {
            disabled: true,
            loading: true,
            label: window.zkpmT('Verifying…'),
        });

        try {
            const saltResponse = await fetch(`/kdf-salt?email=${encodeURIComponent(email)}`, {
                headers: { 'Accept': 'application/json' },
            });
            const saltData = await saltResponse.json().catch(() => ({}));
            const kdfSalt = saltData.kdf_salt;
            if (typeof kdfSalt !== 'string' || kdfSalt.length === 0) {
                throw new Error(window.zkpmT('Login failed.'));
            }

            const masterKey = await deriveMasterKey(password, kdfSalt);
            const encryptionKey = await deriveEncryptionKey(masterKey);
            const authHashInput = await deriveAuthHashInput(masterKey);

            persistEncryptionKey(encryptionKey);

            document.getElementById('auth-hash-input').value = authHashInput;
            form.submit();
        } catch (error) {
            showError('login-error', error instanceof Error ? error.message : window.zkpmT('Login failed.'));
            setButtonState(submitButton, { disabled: false, label: window.zkpmT('Continue') });
        }
    });
}

/**
 * Bind the TOTP challenge page (mandatory factor 2 for login).
 *
 * The code is verified server-side against the at-rest encrypted secret;
 * the Encryption Key already sits in sessionStorage from the login page
 * and is carried across the redirect to the dashboard.
 *
 * @returns {void}
 */
function initTotpChallenge() {
    const form = document.getElementById('totp-challenge-form');
    if (form === null) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError('totp-error');

        const submitButton = document.getElementById('totp-submit');
        submitButton.disabled = true;
        submitButton.textContent = window.zkpmT('Verifying…');

        try {
            const result = await postJson('/totp/verify', {
                code: document.getElementById('totp-code').value,
            });
            window.location.href = result.redirect;
        } catch (error) {
            showError('totp-error', error.message);
            submitButton.disabled = false;
            submitButton.textContent = window.zkpmT('Verify');
        }
    });
}

/**
 * Bind the mandatory TOTP enrollment page.
 *
 * Displays the once-only Recovery Key, then lets the user generate a
 * secret (rendered as a QR code), verify it with a 6-digit code, and
 * enable TOTP. The server marks `is_totp_complete` on success.
 *
 * @returns {void}
 */
function initTotpSetup() {
    const recoveryKey = sessionStorage.getItem(RECOVERY_KEY_STORAGE_KEY);
    if (recoveryKey !== null) {
        const panel = document.getElementById('recovery-key-panel');
        const display = document.getElementById('recovery-key');
        if (panel !== null && display !== null) {
            panel.classList.remove('hidden');
            display.textContent = recoveryKey;
        }
        // Shown exactly once: remove from storage as soon as it is rendered.
        sessionStorage.removeItem(RECOVERY_KEY_STORAGE_KEY);
    }

    const generateButton = document.getElementById('totp-generate');
    if (generateButton === null) return;

    generateButton.addEventListener('click', async () => {
        hideError('totp-error');

        try {
            const result = await postJson('/totp/setup/options', {});

            const { default: QRCode } = await import('qrcode');
            document.getElementById('totp-secret').textContent = result.secret;
            await QRCode.toCanvas(document.getElementById('totp-qr'), result.uri, { width: 220 });
            document.getElementById('totp-secret-panel').classList.remove('hidden');
            generateButton.classList.add('hidden');
        } catch (error) {
            showError('totp-error', error.message);
        }
    });

    const copySecretButton = document.getElementById('totp-copy-secret');
    if (copySecretButton !== null) {
        copySecretButton.addEventListener('click', async () => {
            const secret = document.getElementById('totp-secret').textContent;
            if (!secret) return;
            try {
                await navigator.clipboard.writeText(secret);
                window.zkpmToast(window.zkpmT('Copied!'), 'success');
            } catch {
                window.zkpmToast(window.zkpmT('Copy failed.'), 'error');
            }
        });
    }

    const confirmButton = document.getElementById('totp-confirm');
    confirmButton.addEventListener('click', async () => {
        hideError('totp-error');
        confirmButton.disabled = true;

        try {
            const result = await postJson('/totp/setup/verify', {
                code: document.getElementById('totp-code').value,
            });
            window.location.href = result.redirect;
        } catch (error) {
            showError('totp-error', error.message);
            confirmButton.disabled = false;
        }
    });
}

/**
 * Bind the multi-step account recovery page.
 *
 * Step 1 requests an OTP; step 2 submits the OTP plus the recovery key
 * (released by the server only after the OTP gate) and verifies the key
 * locally against the recovery blob; step 3 re-derives a new master key
 * and submits the fresh auth hash input.
 *
 * @returns {void}
 */
function initRecovery() {
    const otpForm = document.getElementById('recovery-otp-form');
    if (otpForm === null) return;

    otpForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError('recovery-error');

        const submitButton = document.getElementById('recovery-otp-submit');
        submitButton.disabled = true;

        try {
            await postJson('/recovery/request-otp', { email: document.getElementById('recovery-email').value });
            document.getElementById('recovery-otp-note').classList.remove('hidden');
            document.getElementById('step-1').classList.add('hidden');
            document.getElementById('step-2').classList.remove('hidden');
        } catch (error) {
            showError('recovery-error', error.message);
        } finally {
            submitButton.disabled = false;
        }
    });

    const verifyForm = document.getElementById('recovery-verify-form');
    verifyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError('recovery-error');

        const submitButton = document.getElementById('recovery-verify-submit');
        submitButton.disabled = true;
        submitButton.textContent = window.zkpmT('Unlocking…');

        try {
            const email = document.getElementById('recovery-email').value;
            const result = await postJson('/recovery/verify', {
                email,
                otp: document.getElementById('recovery-otp').value,
            });

            // Prove the recovery key locally before allowing the reset.
            const recoveryKey = document.getElementById('recovery-key-input').value.trim();
            const keyBytes = await decryptRecoveryBlob(recoveryKey, result.encrypted_recovery_blob);

            if (!(await verifyVaultCanary(keyBytes, result.vault_canary))) {
                throw new Error(window.zkpmT('Recovery key does not match this vault.'));
            }

            // Keep the email for the reset step.
            document.getElementById('step-2').classList.add('hidden');
            document.getElementById('step-3').classList.remove('hidden');
        } catch (error) {
            showError('recovery-error', error.message);
            submitButton.disabled = false;
            submitButton.textContent = window.zkpmT('Unlock recovery');
        }
    });

    const passwordForm = document.getElementById('recovery-password-form');
    passwordForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError('recovery-error');

        const password = document.getElementById('new-master-password').value;
        const confirmation = document.getElementById('new-master-password-confirm').value;

        if (password !== confirmation) {
            showError('recovery-error', window.zkpmT('Passwords do not match.'));
            return;
        }

        const submitButton = document.getElementById('recovery-password-submit');
        submitButton.disabled = true;

        try {
            // Derive fresh KDF material entirely client-side; never transmit raw passwords.
            const salt = generateKdfSalt();
            const masterKey = await deriveMasterKey(password, salt);
            const newAuthHashInput = await deriveAuthHashInput(masterKey);
            const kdfParams = JSON.stringify({
                algorithm: 'PBKDF2-SHA256',
                iterations: 600000,
                hash: 'SHA-256',
            });

            // Feed the form's hidden crypto payload inputs (documented contract)
            document.getElementById('recovery-new-auth-hash').value = newAuthHashInput;
            document.getElementById('recovery-kdf-salt').value = salt;
            document.getElementById('recovery-kdf-params').value = kdfParams;

            const response = await fetch('/recovery/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    new_auth_hash_input: document.getElementById('recovery-new-auth-hash').value,
                    kdf_salt: document.getElementById('recovery-kdf-salt').value,
                    kdf_params: document.getElementById('recovery-kdf-params').value,
                }),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || window.zkpmT('Failed to reset password.'));
            }

            // Server redirects to the mandatory 2FA re-enrollment.
            window.location.href = response.url;
        } catch (error) {
            showError('recovery-error', error.message);
            submitButton.disabled = false;
        }
    });
}

/**
 * Boot the handlers relevant to the current page.
 *
 * @returns {void}
 */
export function initAuth() {
    initLiveCheck('email', 'email-valid-icon', (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim()));
    initLiveCheck('username', 'username-valid-icon', (value) => value.trim().length >= 3);
    initCapsLockHints();
    initRegisterStrengthMeter();
    initPasswordMatch();
    initRegisterGenerator();
    initRegistration();
    initLogin();
    initTotpChallenge();
    initTotpSetup();
    initRecovery();
}
