/**
 * Vault Item and Category Management Module.
 *
 * Implements SRS FR-4, FR-5, and FR-6:
 * - Client-Side AES-256-GCM encryption/decryption of passwords and notes before transport.
 * - Category management (filtering, assignment, creation, deletion).
 * - Live breach detection (k-Anonymity SHA-1 lookup) on password input.
 * - Real-time client-side search filtering.
 * - Visual password strength / entropy meter.
 * - Integration with the vault auto-lock and unlock events.
 *
 * All user-facing strings resolve through window.zkpmT (i18n-script) so the
 * dynamic UI stays fully localized (Arabic/RTL included) with the server.
 */

import { checkPasswordBreach } from './breach.js';
import { decryptVaultPayload, encryptVaultPayload, randomBytes } from './crypto.js';
import { generateRandomString } from './generator.js';

/** API Endpoints */
const ROUTES = {
    vaultList: '/vault/items',
    vaultStore: '/vault',
    vaultUpdate: (id) => `/vault/${id}`,
    vaultDestroy: (id) => `/vault/${id}`,
    categoryList: '/categories',
    categoryStore: '/categories',
    categoryUpdate: (id) => `/categories/${id}`,
    categoryDestroy: (id) => `/categories/${id}`,
};

/**
 * Strength-meter visual configuration per level.
 *
 * @type {Array<{bar: string, width: string}>}
 */
const STRENGTH_LEVELS = [
    { bar: 'bg-slate-300 dark:bg-slate-600', width: '5%' },
    { bar: 'bg-red-500', width: '20%' },
    { bar: 'bg-orange-500', width: '40%' },
    { bar: 'bg-amber-500', width: '60%' },
    { bar: 'bg-emerald-500', width: '80%' },
    { bar: 'bg-blue-500', width: '100%' },
];

/** In-memory state for items and categories (cleared on vault lock). */
let items = [];
let categories = [];
let selectedCategoryId = null; // null means 'All'
let searchQuery = '';

/** Debounce timer for live breach checking. */
let breachCheckTimer = null;

/** Debounce timer for strength-meter updates. */
let strengthTimer = null;

/**
 * Retrieve the CSRF token from the HTML meta tag.
 *
 * @returns {string} CSRF token.
 */
function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

/**
 * Execute an authenticated JSON HTTP request with CSRF verification.
 *
 * @param {string} url - Target URL.
 * @param {string} method - HTTP method.
 * @param {Object|null} payload - JSON request payload.
 * @returns {Promise<Object>} Parsed JSON response.
 * @throws {Error} On non-2xx status code.
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

    const response = await fetch(url, options);
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || window.zkpmT('Request failed.'));
    }

    return data;
}

/**
 * Show error message inside the item modal.
 *
 * @param {string} message - Error text.
 */
function showFormError(message) {
    const box = document.getElementById('vault-item-error');
    if (box !== null) {
        box.textContent = message;
        box.classList.remove('hidden');
    }
}

/**
 * Hide error message inside the item modal.
 */
function hideFormError() {
    const box = document.getElementById('vault-item-error');
    if (box !== null) {
        box.classList.add('hidden');
    }
}

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
 * Update the live password strength bar inside the item modal.
 *
 * @param {string} password - The candidate plaintext password.
 */
function updateStrengthMeter(password) {
    const bar = document.getElementById('item-strength-bar');
    const label = document.getElementById('item-strength-label');
    if (bar === null || label === null) return;

    const metrics = passwordMetrics(password);
    const level = STRENGTH_LEVELS[metrics.level];

    bar.className = `zkpm-strength-bar-fill ${level.bar}`;
    bar.style.width = level.width;

    if (metrics.level === 0) {
        label.textContent = window.zkpmT('Password strength');
        label.classList.add('text-slate-400');
    } else {
        label.textContent = `${metrics.label} — ${metrics.bits} bits`;
        label.classList.remove('text-slate-400');
    }
}

/**
 * Perform a debounced live breach check on the item password field.
 *
 * Uses k-Anonymity: only a 5-character SHA-1 prefix ever leaves the device.
 *
 * @param {string} password - The candidate password.
 */
function triggerLiveBreachCheck(password) {
    const indicator = document.getElementById('item-password-breach');
    if (!indicator) return;

    if (breachCheckTimer) {
        clearTimeout(breachCheckTimer);
    }

    if (!password || password.length === 0) {
        indicator.classList.add('hidden');
        return;
    }

    indicator.textContent = window.zkpmT('Checking breach status…');
    indicator.className = 'mt-1.5 text-xs text-slate-400';
    indicator.classList.remove('hidden');

    breachCheckTimer = setTimeout(async () => {
        const result = await checkPasswordBreach(password);
        if (result.breached) {
            indicator.textContent = window.zkpmT('Pwned breach count', { count: result.count.toLocaleString() });
            indicator.className = 'mt-1.5 text-xs font-semibold text-red-600 dark:text-red-400';
        } else if (!result.error) {
            indicator.textContent = window.zkpmT('Clean breach status');
            indicator.className = 'mt-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400';
        } else {
            indicator.classList.add('hidden');
        }
    }, 400);
}

/**
 * Fetch categories and vault items from the server.
 *
 * @returns {Promise<void>}
 */
async function loadVaultData() {
    try {
        const [vaultData, catData] = await Promise.all([
            jsonRequest(ROUTES.vaultList),
            jsonRequest(ROUTES.categoryList),
        ]);

        items = vaultData.items ?? [];
        categories = catData.categories ?? [];

        renderCategories();
        renderCategoryOptions();
        renderFilteredList();
    } catch (error) {
        const list = document.getElementById('vault-list');
        if (list !== null) {
            list.innerHTML = `
                <div class="p-4 rounded-lg border border-red-200 bg-red-50/80 text-sm text-red-700 dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-300">
                    ${String(error instanceof Error ? error.message : window.zkpmT('Failed to load vault items.'))}
                </div>`;
        }
    }
}

/**
 * Build a theme-aware category filter pill.
 *
 * @param {string} label - Pill text.
 * @param {boolean} active - Whether the pill represents the selected filter.
 * @param {Function} onClick - Click handler.
 * @returns {HTMLButtonElement} The pill element.
 */
function buildFilterPill(label, active, onClick) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
        active
            ? 'bg-blue-400 text-slate-950 shadow-sm shadow-blue-400/40 hover:bg-blue-300'
            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white'
    }`;
    btn.textContent = label;
    btn.addEventListener('click', onClick);
    return btn;
}

/**
 * Render category filter navigation pills.
 */
function renderCategories() {
    const container = document.getElementById('category-filter-list');
    if (!container) return;

    container.textContent = '';

    container.appendChild(
        buildFilterPill(window.zkpmT('Filter all items', { count: items.length }), selectedCategoryId === null, () => {
            selectedCategoryId = null;
            renderCategories();
            renderFilteredList();
        })
    );

    categories.forEach((cat) => {
        const count = items.filter((it) => it.category_id === cat.id).length;
        container.appendChild(
            buildFilterPill(`${cat.name} (${count})`, selectedCategoryId === cat.id, () => {
                selectedCategoryId = cat.id;
                renderCategories();
                renderFilteredList();
            })
        );
    });
}

/**
 * Populate the category dropdown in the item create/edit modal.
 */
function renderCategoryOptions() {
    const select = document.getElementById('item-category');
    if (!select) return;

    select.innerHTML = `<option value="">${window.zkpmT('No Category')}</option>`;
    categories.forEach((cat) => {
        const opt = document.createElement('option');
        opt.value = cat.id;
        opt.textContent = cat.name;
        select.appendChild(opt);
    });
}

/**
 * Filter and render the vault item list according to category and search query.
 */
function renderFilteredList() {
    const list = document.getElementById('vault-list');
    const empty = document.getElementById('vault-empty');
    const count = document.getElementById('vault-count');

    if (list === null) return;

    list.textContent = '';

    const filtered = items.filter((item) => {
        const matchesCategory =
            selectedCategoryId === null || item.category_id === selectedCategoryId;
        const query = searchQuery.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (item.title && item.title.toLowerCase().includes(query)) ||
            (item.username && item.username.toLowerCase().includes(query)) ||
            (item.url && item.url.toLowerCase().includes(query));

        return matchesCategory && matchesSearch;
    });

    if (empty !== null) {
        empty.classList.toggle('hidden', filtered.length > 0);
    }

    if (count !== null) {
        count.textContent = window.zkpmT('Item count summary', {
            count: filtered.length,
            total: items.length,
        });
    }

    filtered.forEach((item) => {
        list.appendChild(renderItemCard(item));
    });
}

/**
 * Build a single vault item card component (theme-aware, RTL-safe).
 *
 * @param {Object} item - The encrypted vault item record.
 * @returns {HTMLElement} Card element.
 */
function renderItemCard(item) {
    const card = document.createElement('div');
    card.className =
        'flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white/70 p-4 shadow-sm transition hover:border-blue-300 hover:shadow-glow-sm dark:border-slate-800 dark:bg-slate-900/90 dark:hover:border-slate-700';

    const categoryObj = categories.find((c) => c.id === item.category_id);

    const identity = document.createElement('div');
    identity.className = 'min-w-0 flex-1';
    identity.innerHTML = `
        <div class="flex items-center gap-2">
            <h4 class="truncate text-sm font-bold text-slate-900 dark:text-white"></h4>
            ${
                categoryObj
                    ? `<span class="inline-flex items-center shrink-0 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800/60">${String(categoryObj.name).replace(/[<>&]/g, '')}</span>`
                    : ''
            }
        </div>
        <p class="truncate text-xs text-slate-500 dark:text-slate-400 mt-0.5"></p>
        ${
            item.url
                ? `<a href="${item.url}" target="_blank" rel="noopener noreferrer" class="truncate text-xs text-blue-600 hover:underline block mt-1 dark:text-blue-400">${String(item.url).replace(/[<>&]/g, '')}</a>`
                : ''
        }
    `;
    identity.querySelector('h4').textContent = item.title;
    identity.querySelector('p').textContent = item.username;

    const actionGroup = document.createElement('div');
    actionGroup.className = 'flex flex-wrap items-center gap-2 pt-2 sm:pt-0';

    const passwordDisplay = document.createElement('span');
    passwordDisplay.className =
        'font-mono text-xs text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 min-w-[90px] text-center select-all';
    passwordDisplay.textContent = '••••••••••••';

    const maskedPassword = '••••••••••••';

    const revealButton = document.createElement('button');
    revealButton.type = 'button';
    revealButton.className =
        'inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700';
    revealButton.textContent = window.zkpmT('Reveal');
    let isRevealed = false;

    revealButton.addEventListener('click', async () => {
        if (!isRevealed) {
            try {
                const plaintext = await decryptVaultPayload(item.encrypted_password, item.iv);
                passwordDisplay.textContent = plaintext;
                revealButton.textContent = window.zkpmT('Hide');
                isRevealed = true;
            } catch (error) {
                window.zkpmToast(error instanceof Error ? error.message : window.zkpmT('Decryption failed.'), 'error');
            }
        } else {
            passwordDisplay.textContent = maskedPassword;
            revealButton.textContent = window.zkpmT('Reveal');
            isRevealed = false;
        }
    });

    const copyButton = document.createElement('button');
    copyButton.type = 'button';
    copyButton.className =
        'inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700';
    copyButton.textContent = window.zkpmT('Copy');
    copyButton.addEventListener('click', async () => {
        try {
            const plaintext = await decryptVaultPayload(item.encrypted_password, item.iv);
            await navigator.clipboard.writeText(plaintext);
            copyButton.textContent = window.zkpmT('Copied!');
            window.zkpmToast(window.zkpmT('Copied!'), 'success');
            setTimeout(() => {
                copyButton.textContent = window.zkpmT('Copy');
            }, 1500);
        } catch (error) {
            window.zkpmToast(error instanceof Error ? error.message : window.zkpmT('Copy failed.'), 'error');
        }
    });

    const editButton = document.createElement('button');
    editButton.type = 'button';
    editButton.className =
        'inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700';
    editButton.textContent = window.zkpmT('Edit');
    editButton.addEventListener('click', () => openItemModal(item));

    const deleteButton = document.createElement('button');
    deleteButton.type = 'button';
    deleteButton.className =
        'inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-100 dark:border-red-900/60 dark:bg-red-950/60 dark:text-red-400 dark:hover:bg-red-900/60';
    deleteButton.textContent = window.zkpmT('Delete');
    deleteButton.addEventListener('click', () => deleteItem(item));

    actionGroup.append(passwordDisplay, revealButton, copyButton, editButton, deleteButton);
    card.append(identity, actionGroup);

    return card;
}

/**
 * Open the vault item create or edit modal.
 *
 * @param {Object|null} item - Item to edit, or null for creation.
 */
async function openItemModal(item = null) {
    hideFormError();
    const modal = document.getElementById('vault-item-modal');
    const form = document.getElementById('vault-item-form');
    const heading = document.getElementById('vault-item-heading');
    const breachIndicator = document.getElementById('item-password-breach');
    const passwordInput = document.getElementById('item-password');

    if (!modal || !form) return;

    form.reset();
    form.dataset.itemId = item?.id ?? '';
    if (breachIndicator) breachIndicator.classList.add('hidden');
    if (passwordInput) updateStrengthMeter(passwordInput.value);

    heading.textContent = item === null
        ? window.zkpmT('Create New Vault Item')
        : window.zkpmT('Edit Vault Item');

    renderCategoryOptions();

    if (item !== null) {
        try {
            const password = item.encrypted_password
                ? await decryptVaultPayload(item.encrypted_password, item.iv)
                : '';
            const notes = item.encrypted_notes
                ? await decryptVaultPayload(item.encrypted_notes, item.iv)
                : '';

            form.elements.title.value = item.title || '';
            form.elements.username.value = item.username || '';
            form.elements.password.value = password;
            form.elements.notes.value = notes;
            form.elements.url.value = item.url || '';
            if (form.elements.category_id) {
                form.elements.category_id.value = item.category_id || '';
            }

            updateStrengthMeter(password);
            if (password) triggerLiveBreachCheck(password);
        } catch (error) {
            showFormError(window.zkpmT('Failed to decrypt existing item values.'));
        }
    }

    modal.classList.remove('hidden');

    // Focus the title field for keyboard-driven creation
    const titleInput = document.getElementById('item-title');
    if (titleInput) setTimeout(() => titleInput.focus(), 60);
}

/**
 * Close the vault item modal.
 */
function closeItemModal() {
    const modal = document.getElementById('vault-item-modal');
    if (modal !== null) {
        modal.classList.add('hidden');
    }
}

/**
 * Encrypt and save a vault item record (Create or Update).
 *
 * The ciphertext is written into the form's hidden crypto inputs
 * (encrypted_password / encrypted_notes / iv) before the JSON payload is
 * assembled, keeping the cryptographic payloads explicit in the form.
 *
 * @param {HTMLFormElement} form - Form element.
 */
async function saveItem(form) {
    const password = form.elements.password.value;
    const notes = form.elements.notes.value;

    // Encrypt password and notes with AES-256-GCM client-side (fresh IV per field)
    const passwordCipher = await encryptVaultPayload(password);
    const notesCipher = notes ? await encryptVaultPayload(notes) : null;

    form.elements.encrypted_password.value = passwordCipher.ciphertext;
    form.elements.iv.value = passwordCipher.iv;
    if (notesCipher !== null) {
        form.elements.encrypted_notes.value = notesCipher.ciphertext;
    } else {
        form.elements.encrypted_notes.value = '';
    }

    const payload = {
        title: form.elements.title.value.trim(),
        username: form.elements.username.value.trim(),
        encrypted_password: form.elements.encrypted_password.value,
        encrypted_notes: form.elements.encrypted_notes.value || null,
        iv: form.elements.iv.value,
        url: form.elements.url.value.trim() || null,
        category_id: form.elements.category_id?.value ? form.elements.category_id.value : null,
    };

    const itemId = form.dataset.itemId;
    const data = itemId
        ? await jsonRequest(ROUTES.vaultUpdate(itemId), 'PUT', payload)
        : await jsonRequest(ROUTES.vaultStore, 'POST', payload);

    const saved = data.item;

    if (itemId) {
        items = items.map((it) => (it.id === saved.id ? saved : it));
    } else {
        items.push(saved);
    }

    items.sort((a, b) => a.title.localeCompare(b.title, undefined, { sensitivity: 'base' }));
    renderCategories();
    renderFilteredList();
    closeItemModal();

    window.zkpmToast(window.zkpmT(itemId ? 'Item Updated' : 'Item Created'), 'success');
}

/**
 * Delete a vault item after user confirmation.
 *
 * @param {Object} item - Item record to delete.
 */
async function deleteItem(item) {
    if (!window.confirm(window.zkpmT('Delete vault item confirm'))) {
        return;
    }

    try {
        await jsonRequest(ROUTES.vaultDestroy(item.id), 'DELETE');
        items = items.filter((it) => it.id !== item.id);
        renderCategories();
        renderFilteredList();
        window.zkpmToast(window.zkpmT('Item Deleted'), 'success');
    } catch (error) {
        window.zkpmToast(error instanceof Error ? error.message : window.zkpmT('Delete failed.'), 'error');
    }
}

/**
 * Category Management Modal Operations.
 */
function initCategoryModal() {
    const modal = document.getElementById('category-manager-modal');
    const openBtn = document.getElementById('manage-categories-btn');
    const closeBtn = document.getElementById('category-manager-close');
    const form = document.getElementById('new-category-form');
    const listEl = document.getElementById('category-manager-list');

    if (!modal) return;

    function renderModalCategoryList() {
        if (!listEl) return;
        listEl.textContent = '';

        if (categories.length === 0) {
            listEl.innerHTML = '<p class="text-xs text-slate-400 italic">' + window.zkpmT('No categories created yet.') + '</p>';
            return;
        }

        categories.forEach((cat) => {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-2 p-2 rounded-lg border border-slate-200 bg-slate-50 text-xs dark:border-slate-700 dark:bg-slate-800/60';
            row.innerHTML = `
                <span class="font-semibold text-slate-700 dark:text-slate-200">${String(cat.name).replace(/[<>&]/g, '')}</span>
                <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2 py-0.5 font-semibold text-red-600 transition hover:bg-red-100 dark:border-red-900/60 dark:bg-red-950/60 dark:text-red-400 dark:hover:bg-red-900/60">
                    ${window.zkpmT('Delete')}
                </button>
            `;
            row.querySelector('button').addEventListener('click', async () => {
                if (!window.confirm(window.zkpmT('Delete category confirm'))) return;
                try {
                    await jsonRequest(ROUTES.categoryDestroy(cat.id), 'DELETE');
                    categories = categories.filter((c) => c.id !== cat.id);
                    items = items.map((it) => it.category_id === cat.id ? { ...it, category_id: null } : it);
                    if (selectedCategoryId === cat.id) selectedCategoryId = null;
                    renderModalCategoryList();
                    renderCategories();
                    renderCategoryOptions();
                    renderFilteredList();
                    window.zkpmToast(window.zkpmT('Category deleted'), 'success');
                } catch (err) {
                    window.zkpmToast(err instanceof Error ? err.message : window.zkpmT('Failed to delete category.'), 'error');
                }
            });
            listEl.appendChild(row);
        });
    }

    if (openBtn) {
        openBtn.addEventListener('click', () => {
            renderModalCategoryList();
            modal.classList.remove('hidden');
            const input = document.getElementById('new-category-name');
            if (input) setTimeout(() => input.focus(), 60);
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    });

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('new-category-name');
            if (!input || !input.value.trim()) return;

            try {
                const res = await jsonRequest(ROUTES.categoryStore, 'POST', { name: input.value.trim() });
                categories.push(res.category);
                categories.sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }));
                input.value = '';
                renderModalCategoryList();
                renderCategories();
                renderCategoryOptions();
                renderFilteredList();
                window.zkpmToast(window.zkpmT('Category created'), 'success');
            } catch (err) {
                window.zkpmToast(err instanceof Error ? err.message : window.zkpmT('Failed to create category.'), 'error');
            }
        });
    }
}

/**
 * Initialize Vault and Category UI event listeners.
 */
export function initVault() {
    const modal = document.getElementById('vault-item-modal');
    if (modal === null) return;

    const form = document.getElementById('vault-item-form');
    const searchInput = document.getElementById('vault-search');
    const passwordInput = document.getElementById('item-password');
    const strengthBar = document.getElementById('item-strength-bar');

    // React to vault unlock and auto-lock events
    window.addEventListener('zkpm:vault-unlocked', () => {
        loadVaultData();
    });

    window.addEventListener('zkpm:vault-locked', () => {
        items = [];
        categories = [];
        closeItemModal();
        renderFilteredList();
    });

    // Search bar filter
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            renderFilteredList();
        });
    }

    // Live breach check + strength meter on password typing in modal
    if (passwordInput) {
        passwordInput.addEventListener('input', (e) => {
            triggerLiveBreachCheck(e.target.value);
            updateStrengthMeter(e.target.value);
        });
    }

    if (strengthBar) {
        updateStrengthMeter('');
    }

    document.getElementById('vault-add')?.addEventListener('click', () => openItemModal());
    document.getElementById('vault-generate')?.addEventListener('click', () => {
        const pass = generateRandomString({ length: 24, uppercase: true, lowercase: true, numbers: true, symbols: true });
        if (passwordInput) {
            passwordInput.value = pass;
            triggerLiveBreachCheck(pass);
            updateStrengthMeter(pass);
        }
    });
    document.getElementById('vault-item-cancel')?.addEventListener('click', closeItemModal);

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideFormError();

            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            try {
                await saveItem(form);
            } catch (error) {
                showFormError(error instanceof Error ? error.message : window.zkpmT('Save failed.'));
            } finally {
                submitButton.disabled = false;
            }
        });
    }

    initCategoryModal();

    // Notify listeners (e.g. deep links like /vault?add=1) that the
    // vault UI is wired and safe to interact with.
    window.dispatchEvent(new Event('zkpm:vault-ready'));

    // If already unlocked on initial load
    if (
        document.getElementById('unlock-form') === null ||
        document.getElementById('unlock-form').classList.contains('hidden')
    ) {
        loadVaultData();
    }
}