{{-- Client-side auto-lock modal overlay (un-dismissible while vault is locked) --}}
<div id="lock-modal"
     class="fixed inset-0 z-50 hidden flex flex-col overflow-y-auto bg-black/85 backdrop-blur-md px-4 [padding-top:max(env(safe-area-inset-top),1rem)] [padding-bottom:max(env(safe-area-inset-bottom),1.5rem)] select-none md:items-center md:justify-center"
     role="dialog"
     aria-modal="true"
     aria-labelledby="lock-modal-title">
    {{-- Keyboard-safe on mobile (base default): top-anchored with a max-height
         that fits above/around the on-screen keyboard, so the biometric button
         and unlock action stay reachable without scrolling. Desktop resumes a
         centred card via the `.lock-modal-card` rules at `md`. --}}
    <div class="lock-modal-card w-full max-w-md md:m-auto glass-card rounded-2xl px-5 py-6 sm:px-8 sm:py-6 shadow-2xl space-y-5 text-center animate-scale-in">
        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-blue-100 dark:bg-blue-950/80 border border-blue-200 dark:border-blue-800/60 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto shadow-glow">
            <x-icon-lock class="w-6 h-6 sm:w-7 sm:h-7" />
        </div>

        <div>
            <div class="flex items-center justify-center gap-2 mb-1">
                <h2 id="lock-modal-title" class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">{{ __('Vault Inactive & Locked') }}</h2>
                <x-security-badge type="warning" :label="__('Locked')" />
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ __('Keys were securely cleared from browser memory. Enter your Master Password to unlock.') }}
            </p>
        </div>

        <div id="lock-error"
             class="hidden rounded-xl border border-red-300 dark:border-red-900/60 bg-red-50 dark:bg-red-950/80 p-3 text-xs text-red-700 dark:text-red-300"
             role="alert"></div>

        {{-- Biometric unlock is the DEFAULT, most prominent choice on mobile —
             typing a Master Password on a phone keyboard is the friction-heavy
             fallback. Shown above the password form and larger on phones. --}}
        <button type="button"
                id="unlock-webauthn"
                class="unlock-webauthn-btn hidden w-full inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-400/60 bg-white/40 px-4 py-3.5 sm:py-2.5 min-h-12 text-xs font-bold text-emerald-700 uppercase tracking-wider backdrop-blur-glass shadow-glass-sm transition duration-200 ease-in-out hover:-translate-y-px hover:bg-white/60 hover:border-emerald-500/80 hover:shadow-glass active:translate-y-0 active:scale-[0.97] dark:border-emerald-400/30 dark:bg-white/[0.06] dark:text-emerald-300 dark:hover:bg-white/[0.1] disabled:opacity-50">
            <x-icon-webauthn class="w-5 h-5" />
            {{ __('Unlock with Biometric Passkey') }}
        </button>

        {{-- Fallback divider --}}
        <div class="flex items-center gap-3" aria-hidden="true">
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ __('Or use Master Password') }}</span>
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
        </div>

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
                   class="mt-1 zkpm-input min-h-11">

            <button type="submit"
                    id="lock-unlock-submit"
                    class="zkpm-btn-primary w-full min-h-11">
                {{ __('Unlock Vault') }}
            </button>
        </form>
    </div>
</div>