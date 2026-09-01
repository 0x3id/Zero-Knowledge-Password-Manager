/**
 * WebAuthn Passkey Management Module (Profile / Settings).
 *
 * Implements SRS FR-3:
 * - Enrolls new hardware/biometric security keys or platform authenticators.
 * - Lists registered credentials with friendly device labels.
 * - Allows removing individual credentials.
 */

import { createWebauthnCredential, webauthnErrorMessage } from './webauthn.js';

/**
 * Retrieve CSRF token from page meta tag.
 *
 * @returns {string} CSRF token.
 */
function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

/**
 * Perform JSON request.
 *
 * @param {string} url - Target URL.
 * @param {string} method - HTTP method.
 * @param {Object|null} payload - Request payload.
 * @returns {Promise<Object>} Response JSON.
 */
async function jsonRequest(url, method = 'GET', payload = null) {
    const options = {
        method,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
    };

    if (payload !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(payload);
    }

    const res = await fetch(url, options);
    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        throw new Error(data.message || window.zkpmT('Request failed.'));
    }

    return data;
}

/**
 * Wire the WebAuthn passkey management section on the Profile page.
 */
export function initWebauthnManager() {
    const container = document.getElementById('webauthn-manager-container');
    if (!container) return;

    const listEl = document.getElementById('webauthn-list');
    const registerBtn = document.getElementById('webauthn-register-btn');
    const errorEl = document.getElementById('webauthn-error');

    function showError(msg) {
        if (!errorEl) return;
        errorEl.textContent = msg;
        errorEl.classList.remove('hidden');
    }

    function hideError() {
        if (!errorEl) return;
        errorEl.classList.add('hidden');
    }

    async function loadCredentials() {
        if (!listEl) return;
        try {
            const data = await jsonRequest('/webauthn/credentials');
            const credentials = data.credentials || [];

            listEl.textContent = '';
            if (credentials.length === 0) {
                listEl.innerHTML = '<p class="text-xs text-slate-400 italic">' + window.zkpmT('No passkeys or security keys registered yet.') + '</p>';
                return;
            }

            credentials.forEach((cred) => {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between gap-2 p-3 rounded-lg border border-slate-200 bg-slate-50 text-xs dark:border-slate-700 dark:bg-slate-800/60';
                item.innerHTML = `
                    <div>
                        <p class="font-semibold text-slate-800 dark:text-slate-200"></p>
                        <p class="text-slate-500 dark:text-slate-400 text-[10px]"></p>
                    </div>
                    <button type="button" class="delete-passkey-btn inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 font-semibold text-red-600 transition hover:bg-red-100 dark:border-red-900/60 dark:bg-red-950/60 dark:text-red-400 dark:hover:bg-red-900/60">
                        ${window.zkpmT('Remove')}
                    </button>
                `;
                item.querySelector('p.font-semibold').textContent = cred.device_label || window.zkpmT('Security Key');
                item.querySelector('p.text-slate-500').textContent = `${window.zkpmT('Added')} ${new Date(cred.created_at).toLocaleDateString()}`;

                item.querySelector('.delete-passkey-btn').addEventListener('click', async () => {
                    if (!window.confirm(window.zkpmT('Remove passkey confirm', { label: cred.device_label || window.zkpmT('Security Key') }))) return;
                    try {
                        await jsonRequest(`/webauthn/credentials/${cred.id}`, 'DELETE');
                        loadCredentials();
                        window.zkpmToast(window.zkpmT('Passkey Removed'), 'success');
                    } catch (err) {
                        showError(err instanceof Error ? err.message : window.zkpmT('Failed to delete credential.'));
                    }
                });

                listEl.appendChild(item);
            });
        } catch (err) {
            showError(window.zkpmT('Failed to load passkeys.'));
        }
    }

    if (registerBtn) {
        registerBtn.addEventListener('click', async () => {
            hideError();
            registerBtn.disabled = true;
            registerBtn.textContent = window.zkpmT('Waiting for authenticator…');

            try {
                const options = await jsonRequest('/webauthn/register/options', 'POST', {});
                const credResponse = await createWebauthnCredential(options);
                const label = window.prompt(window.zkpmT('Passkey device label prompt'), window.zkpmT('Security Key')) || window.zkpmT('Security Key');

                await jsonRequest('/webauthn/register/verify', 'POST', {
                    ...credResponse,
                    device_label: label,
                });

                loadCredentials();
                window.zkpmToast(window.zkpmT('Passkey Added'), 'success');
            } catch (err) {
                showError(webauthnErrorMessage(err));
            } finally {
                registerBtn.disabled = false;
                registerBtn.textContent = window.zkpmT('Register New Passkey');
            }
        });
    }

    loadCredentials();
}
