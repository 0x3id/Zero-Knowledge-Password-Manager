<section id="webauthn-manager-container">
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                {{ __('WebAuthn & Biometric Passkeys') }}
            </h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ __('WebAuthn description') }}
            </p>
        </div>
        <button type="button"
                id="webauthn-register-btn"
                class="zkpm-btn-primary">
            <x-icon-plus class="w-4 h-4" />
            {{ __('Register New Passkey') }}
        </button>
    </header>

    <div id="webauthn-error" class="mt-4 hidden p-3 rounded-xl bg-red-950/60 border border-red-800/60 text-xs text-red-300"></div>

    <div class="mt-6 space-y-3">
        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Enrolled authenticators') }}</label>
        <div id="webauthn-list" class="space-y-2"></div>
    </div>
</section>
