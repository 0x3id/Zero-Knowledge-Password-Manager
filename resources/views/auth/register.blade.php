<x-guest-layout>
    <div class="mb-4 flex items-center gap-2">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Register') }}</h2>
        <x-security-badge type="encrypted" :label="__('Zero-Knowledge')" />
    </div>

    <form id="register-form" method="POST" action="{{ route('register.submit') }}">
        @csrf

        {{-- Client-derived crypto payloads (never the raw password) --}}
        <input type="hidden" name="auth_hash_input" id="auth-hash-input">
        <input type="hidden" name="kdf_salt" id="kdf-salt">
        <input type="hidden" name="kdf_params" id="kdf-params">
        <input type="hidden" name="encrypted_recovery_blob" id="encrypted-recovery-blob">
        <input type="hidden" name="vault_canary" id="vault-canary">

        <div>
            <x-input-label for="username" :value="__('Username')" class="zkpm-label" />
            <div class="relative mt-1">
                <x-icon-user class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                <x-text-input id="username" name="username" type="text" class="block w-full ps-9 pe-9" required autocomplete="username" minlength="3" />
                <x-icon-check id="username-valid-icon" data-username-check
                     class="pointer-events-none absolute end-3 top-1/2 hidden h-4 w-4 -translate-y-1/2 text-[var(--vc-accent)]" />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" class="zkpm-label" />
            <div class="relative mt-1">
                <x-icon-mail class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                <x-text-input id="email" name="email" type="email" class="block w-full ps-9 pe-9" required autocomplete="email" />
                <x-icon-check id="email-valid-icon" data-email-check
                     class="pointer-events-none absolute end-3 top-1/2 hidden h-4 w-4 -translate-y-1/2 text-[var(--vc-accent)]" />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="mt-4" x-data="{ show: false }">
            <div class="flex items-center justify-between gap-2">
                <x-input-label for="master-password" :value="__('Master Password')" class="zkpm-label" />
                <button type="button"
                        id="register-generate-btn"
                        title="{{ __('Generate Strong') }}"
                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-600 transition hover:bg-blue-50 active:scale-95 dark:text-blue-400 dark:hover:bg-blue-950/60">
                    <x-icon-generator class="h-3.5 w-3.5" />
                    {{ __('Generate Strong') }}
                </button>
            </div>
            <div class="relative mt-1">
                <x-icon-lock class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                <input id="master-password" name="master_password" type="password" minlength="12" required autocomplete="new-password"
                       :type="show ? 'text' : 'password'"
                       placeholder="••••••••••••"
                       data-capslock data-capslock-hint="register-caps-hint"
                       class="zkpm-input ps-9 pe-10">
                <button type="button"
                        @click="show = ! show"
                        :aria-label="show ? 'Hide' : 'Reveal'"
                        class="absolute end-2.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 transition dark:hover:text-slate-200 active:scale-90">
                    <x-icon-biometric x-show="! show" class="h-4 w-4" />
                </button>
            </div>
            <p id="register-caps-hint" data-capslock-panel
               class="mt-1.5 hidden items-center gap-1 text-[11px] font-semibold text-amber-600 dark:text-amber-400"
               role="status">
                <x-icon-verified class="h-3.5 w-3.5" />
            <div class="mt-3 flex items-center gap-2" aria-hidden="true">
                <div class="zkpm-strength-bar flex-1">
                    <div id="register-strength-bar" data-strength-bar class="zkpm-strength-bar-fill bg-slate-300 dark:bg-slate-600" style="width: 0%"></div>
                </div>
                <span id="register-strength-label" data-strength-label class="whitespace-nowrap text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Password strength') }}</span>
            </div>
            <p id="register-length-hint" data-strength-note
               class="mt-1 text-[10px] text-slate-400 dark:text-slate-500 min-h-[14px]">
                {{ __('Min 12 characters') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('master_password')" />
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                {{ __('Register KDF note') }}
            </p>
        </div>

        <div class="mt-4" x-data="{ show: false }">
            <div class="flex items-center justify-between gap-2">
                <x-input-label for="master-password-confirm" :value="__('Confirm master password')" class="zkpm-label" />
                <span id="register-match" data-match-indicator
                      class="hidden items-center gap-1 text-[10px] font-bold uppercase tracking-wider"
                      role="status"></span>
            </div>
            <div class="relative mt-1">
                <x-icon-lock class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                <input id="master-password-confirm" type="password" required autocomplete="new-password"
                       :type="show ? 'text' : 'password'"
                       placeholder="••••••••••••"
                       data-capslock data-capslock-hint="register-caps-hint-confirm"
                       class="zkpm-input ps-9 pr-10">
                <button type="button"
                        @click="show = ! show"
                        :aria-label="show ? 'Hide' : 'Reveal'"
                        class="absolute end-2.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 transition dark:hover:text-slate-200 active:scale-90">
                    <x-icon-biometric x-show="! show" class="h-4 w-4" />
                </button>
            </div>
            <p id="register-caps-hint-confirm" data-capslock-panel
               class="mt-1.5 hidden items-center gap-1 text-[11px] font-semibold text-amber-600 dark:text-amber-400"
               role="status">
                <x-icon-verified class="h-3.5 w-3.5" /> class="mt-4 hidden rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800/60 p-3 text-sm text-red-700 dark:text-red-300"></div>

        <div class="mt-6 flex items-center justify-between gap-4">
            <a class="text-sm text-slate-600 dark:text-slate-400 underline hover:text-blue-600 dark:hover:text-blue-400 transition" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <button type="submit" id="register-submit" class="zkpm-btn-primary min-w-[140px] active:scale-95">
                <svg data-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span data-label>{{ __('Register') }}</span>
            </button>
        </div>

        <p class="mt-5 flex items-center gap-1.5 text-[10px] text-slate-400 dark:text-slate-500">
            <x-icon-verified class="h-3 w-3 shrink-0" />
            {{ __('Register zero-knowledge note') }}
        </p>
    </form>
</x-guest-layout>