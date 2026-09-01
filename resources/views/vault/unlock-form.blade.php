{{-- Inline vault unlock form (shown when encryption key is not in memory) --}}
<div id="unlock-form-container">
    <form id="unlock-form" class="glass-card p-8 max-w-lg mx-auto text-center space-y-5 animate-scale-in">
        @csrf
        <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto shadow-glow-sm">
            <x-icon-lock class="w-7 h-7" />
        </div>

        <div>
            <div class="flex items-center justify-center gap-2 mb-1">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Unlock Your Vault') }}</h3>
                <x-security-badge type="encrypted" :label="__('Zero-Knowledge')" />
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                {{ __('Enter your Master Password to derive your AES-256 key locally. The key never leaves your browser.') }}
            </p>
        </div>

        <div class="text-start space-y-2">
            <x-input-label for="unlock-password" :value="__('Master Password')" class="zkpm-label" />
            <x-text-input id="unlock-password" type="password" class="mt-1 block w-full zkpm-input" placeholder="••••••••••••" autocomplete="current-password" required />
        </div>

        <div class="space-y-2 pt-1">
            <button type="submit" id="unlock-submit" class="zkpm-btn-primary w-full">
                <x-icon-lock class="w-4 h-4" />
                {{ __('Unlock Vault') }}
            </button>

            <button type="button" class="unlock-webauthn-btn hidden zkpm-btn-secondary w-full">
                <x-icon-webauthn class="w-4 h-4" />
                {{ __('Unlock with Biometric Passkey') }}
            </button>
        </div>
    </form>
</div>
