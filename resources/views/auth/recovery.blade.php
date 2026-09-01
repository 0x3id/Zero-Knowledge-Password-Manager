<x-guest-layout>
    <div class="mb-4">
        <div class="flex items-center gap-2 mb-1">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Account recovery') }}</h2>
            <x-security-badge type="warning" :label="__('Recovery')" />
        </div>
        <p class="text-sm text-slate-600 dark:text-slate-400">
            {{ __('Recover your vault with your recovery key and the email on your account.') }}
        </p>
    </div>

    <div id="recovery-error" class="mb-4 hidden rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800/60 p-3 text-sm text-red-700 dark:text-red-300"></div>

    <div id="step-1">
        <form id="recovery-otp-form" class="space-y-4">
            @csrf
            <x-input-label for="recovery-email" :value="__('Email')" class="zkpm-label" />
            <x-text-input id="recovery-email" type="email" class="mt-1 block w-full" autocomplete="username" required placeholder="you@example.com" />
            <button type="submit" id="recovery-otp-submit" class="zkpm-btn-primary w-full">
                {{ __('Send recovery code') }}
            </button>
            <p id="recovery-otp-note" class="hidden text-center text-xs text-slate-500 dark:text-slate-400">
                {{ __('If that email is registered, a code has been sent.') }}
            </p>
        </form>
    </div>

    <div id="step-2" class="hidden">
        <form id="recovery-verify-form" class="space-y-4">
            @csrf
            <div>
                <x-input-label for="recovery-otp" :value="__('6-digit code from email')" class="zkpm-label" />
                <x-text-input id="recovery-otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" class="mt-1 block w-full text-center text-2xl tracking-[0.5em]" required />
            </div>
            <div x-data="{ showKey: false }">
                <x-input-label for="recovery-key-input" :value="__('Recovery key')" class="zkpm-label" />
                <div class="relative mt-1">
                    <input id="recovery-key-input" type="text"
                           :type="showKey ? 'text' : 'password'"
                           autocomplete="off" required
                           placeholder="xxxx-xxxx-xxxx-xxxx-xxxx"
                           class="zkpm-input pr-10 font-mono">
                    <button type="button"
                            @click="showKey = ! showKey"
                            :aria-label="showKey ? 'Hide' : 'Reveal'"
                            class="absolute end-2.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 transition dark:hover:text-slate-200">
                        <x-icon-biometric x-show="! showKey" class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <button type="submit" id="recovery-verify-submit" class="zkpm-btn-primary w-full">
                {{ __('Unlock recovery') }}
            </button>
            <p class="text-center text-xs text-slate-500 dark:text-slate-400">
                {{ __('The recovery key is verified entirely in your browser — it is never sent over the network.') }}
            </p>
        </form>
    </div>

    <div id="step-3" class="hidden">
        <form id="recovery-password-form" class="space-y-4">
            @csrf

            {{-- Client-derived replacement crypto payloads generated after recovery --}}
            <input type="hidden" name="recovery_new_auth_hash" id="recovery-new-auth-hash">
            <input type="hidden" name="recovery_kdf_salt" id="recovery-kdf-salt">
            <input type="hidden" name="recovery_kdf_params" id="recovery-kdf-params">

            <div x-data="{ show: false }">
                <x-input-label for="new-master-password" :value="__('New master password')" class="zkpm-label" />
                <div class="relative mt-1">
                    <input id="new-master-password" type="password" minlength="12" autocomplete="new-password" required
                           :type="show ? 'text' : 'password'"
                           placeholder="••••••••••••"
                           class="zkpm-input pr-10">
                    <button type="button"
                            @click="show = ! show"
                            :aria-label="show ? 'Hide' : 'Reveal'"
                            class="absolute end-2.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 transition dark:hover:text-slate-200">
                        <x-icon-biometric x-show="! show" class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <div x-data="{ show: false }">
                <x-input-label for="new-master-password-confirm" :value="__('Confirm new master password')" class="zkpm-label" />
                <div class="relative mt-1">
                    <input id="new-master-password-confirm" type="password" autocomplete="new-password" required
                           :type="show ? 'text' : 'password'"
                           placeholder="••••••••••••"
                           class="zkpm-input pr-10">
                    <button type="button"
                            @click="show = ! show"
                            :aria-label="show ? 'Hide' : 'Reveal'"
                            class="absolute end-2.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 transition dark:hover:text-slate-200">
                        <x-icon-biometric x-show="! show" class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <button type="submit" id="recovery-password-submit" class="zkpm-btn-success w-full">
                {{ __('Set password and re-enroll 2FA') }}
            </button>
            <p class="text-center text-xs text-emerald-600 dark:text-emerald-400">
                {{ __('Vault recovered — your encrypted data stays intact.') }}
            </p>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
        <a class="underline hover:text-blue-600 dark:hover:text-blue-400 transition" href="{{ route('login') }}">{{ __('Back to login') }}</a>
    </p>
</x-guest-layout>