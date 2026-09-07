{{-- Client-side auto-lock modal overlay (un-dismissible while vault is locked) --}}
<div id="lock-modal"
     class="fixed inset-0 z-50 hidden flex overflow-y-auto bg-black/85 backdrop-blur-md px-4 [padding-top:max(env(safe-area-inset-top),1rem)] [padding-bottom:max(env(safe-area-inset-bottom),1.5rem)] select-none"
     role="dialog"
     aria-modal="true"
     aria-labelledby="lock-modal-title">
    <div class="w-full max-w-md m-auto glass-card rounded-2xl p-6 sm:p-8 py-6 shadow-2xl space-y-5 text-center animate-scale-in">
        <div class="w-14 h-14 rounded-2xl bg-blue-100 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800/60 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto shadow-glow">
            <x-icon-lock class="w-7 h-7" />
        </div>

        <div>
            <div class="flex items-center justify-center gap-2 mb-1">
                <h2 id="lock-modal-title" class="text-xl font-bold text-slate-900 dark:text-white">{{ __('Vault Inactive & Locked') }}</h2>
                <x-security-badge type="warning" :label="__('Locked')" />
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ __('Keys were securely cleared from browser memory. Enter your Master Password to unlock.') }}
            </p>
        </div>

        <div id="lock-error"
             class="hidden rounded-xl border border-red-300 dark:border-red-900/60 bg-red-50 dark:bg-red-950/80 p-3 text-xs text-red-700 dark:text-red-300"
             role="alert"></div>

        <button type="button"
                id="unlock-webauthn"
                class="unlock-webauthn-btn hidden w-full inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-400/60 bg-white/40 px-4 py-2.5 text-xs font-semibold text-emerald-700 uppercase tracking-wider backdrop-blur-glass shadow-glass-sm transition duration-200 ease-in-out hover:-translate-y-px hover:bg-white/60 hover:border-emerald-500/80 hover:shadow-glass active:translate-y-0 active:scale-[0.97] dark:border-emerald-400/30 dark:bg-white/[0.06] dark:text-emerald-300 dark:hover:bg-white/[0.1] disabled:opacity-50">
            <x-icon-webauthn class="w-4 h-4" />
            {{ __('Unlock with Biometric Passkey') }}
        </button>

        <form id="lock-unlock-form" class="space-y-4 text-start">
            <div class="flex items-center justify-between">
                <label for="lock-unlock-password" class="zkpm-label">{{ __('Master Password') }}</label>
                <span class="font-mono text-[10px] text-slate-500">PBKDF2-SHA256</span>
            </div>
            <input type="password"
                   id="lock-unlock-password"
                   required
                   autocomplete="current-password"
                   placeholder="••••••••••••"
                   class="mt-1 zkpm-input">

            <button type="submit"
                    id="lock-unlock-submit"
                    class="zkpm-btn-primary w-full">
                {{ __('Unlock Vault') }}
            </button>
        </form>
    </div>
</div>