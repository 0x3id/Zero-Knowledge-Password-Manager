{{-- Vault item create/edit modal — ciphertext fields are populated client-side by vault.js --}}
<div id="vault-item-modal"
     class="fixed inset-0 z-50 hidden flex overflow-y-auto bg-black/75 backdrop-blur-md px-4 [padding-top:max(env(safe-area-inset-top),1rem)] [padding-bottom:max(env(safe-area-inset-bottom),1.5rem)]"
     role="dialog"
     aria-modal="true"
     aria-labelledby="vault-item-heading">
    <div class="w-full max-w-lg m-auto glass-card rounded-2xl p-5 sm:p-6 shadow-2xl space-y-4 animate-scale-in">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700/80 pb-3">
            <h3 id="vault-item-heading" class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Create New Vault Item') }}</h3>
            <x-encryption-badge />
        </div>

        <div id="vault-item-error"
             class="hidden rounded-xl border border-red-300 dark:border-red-900/60 bg-red-50 dark:bg-red-950/80 p-3 text-xs text-red-700 dark:text-red-300"
             role="alert"></div>

        <form id="vault-item-form" class="space-y-4 text-start">
            {{-- Hidden crypto payload inputs (populated client-side with AES-256-GCM ciphertext) --}}
            <input type="hidden" name="encrypted_password" id="item-encrypted-password">
            <input type="hidden" name="encrypted_notes" id="item-encrypted-notes">
            <input type="hidden" name="iv" id="item-iv">

            <div>
                <label for="item-title" class="zkpm-label">{{ __('Item Title') }} *</label>
                <input type="text" id="item-title" name="title" required maxlength="255" placeholder="{{ __('Item title placeholder') }}"
                       autocomplete="off"
                       class="mt-1 zkpm-input">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="item-username" class="zkpm-label">{{ __('Username / Email') }} *</label>
                    <input type="text" id="item-username" name="username" required maxlength="255" placeholder="user@domain.com"
                           autocomplete="off"
                           class="mt-1 zkpm-input">
                </div>

                <div>
                    <label for="item-category" class="zkpm-label">{{ __('Category') }}</label>
                    <select id="item-category" name="category_id" class="mt-1 zkpm-input">
                        <option value="">{{ __('No Category') }}</option>
                    </select>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="item-password" class="zkpm-label">{{ __('Password (Encrypted)') }} *</label>
                    <button type="button" id="vault-generate"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1 transition">
                        <x-icon-generator class="w-3.5 h-3.5" />
                        {{ __('Generate Strong') }}
                    </button>
                </div>

                <div x-data="{ show: false }" class="relative mt-1">
                    <input type="password" id="item-password" name="password" required placeholder="••••••••••••"
                           :type="show ? 'text' : 'password'"
                           autocomplete="new-password"
                           class="zkpm-input font-mono pe-10">
                    <button type="button"
                            @click="show = ! show"
                            :aria-label="show ? 'Hide' : 'Reveal'"
                            class="absolute end-2.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                        <x-icon-biometric class="h-4 w-4" />
                    </button>
                </div>

                {{-- Live strength / entropy meter + breach status --}}
                <div class="mt-1.5 flex items-center gap-2">
                    <div class="zkpm-strength-bar flex-1" aria-hidden="true">
                        <div id="item-strength-bar" class="zkpm-strength-bar-fill bg-slate-400 dark:bg-slate-600" style="width: 5%"></div>
                    </div>
                    <span id="item-strength-label" class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ __('Password strength') }}</span>
                </div>
                <p id="item-password-breach" class="hidden text-xs mt-1.5 text-slate-500 dark:text-slate-400"></p>
            </div>

            <div>
                <label for="item-url" class="zkpm-label">{{ __('Website URL (optional)') }}</label>
                <input type="url" id="item-url" name="url" maxlength="2048" placeholder="https://app.example.com/login"
                       autocomplete="off"
                       class="mt-1 zkpm-input">
            </div>

            <div>
                <label for="item-notes" class="zkpm-label">{{ __('Secure Notes (Encrypted, optional)') }}</label>
                <textarea id="item-notes" name="notes" rows="2" placeholder="{{ __('Secure notes placeholder') }}"
                          autocomplete="off"
                          class="mt-1 zkpm-input"></textarea>
            </div>

            <div class="flex flex-col-reverse gap-3 pt-3 border-t border-slate-200 dark:border-slate-700/80 sm:flex-row sm:justify-end">
                <button type="button" id="vault-item-cancel" class="zkpm-btn-secondary w-full sm:w-auto">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="zkpm-btn-primary w-full sm:w-auto">
                    {{ __('Save Item') }}
                </button>
            </div>
        </form>
    </div>
</div>