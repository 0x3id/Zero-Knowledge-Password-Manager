<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-4 flex items-center gap-2">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Sign in') }}</h2>
        <x-security-badge type="encrypted" :label="__('Zero-Knowledge')" />
    </div>

    <form id="login-form" method="POST" action="{{ route('login.submit') }}">
        @csrf

        {{-- Client-derived auth hash (never the raw password) --}}
        <input type="hidden" name="auth_hash_input" id="auth-hash-input">

        <div>
            <x-input-label for="email" :value="__('Email')" class="zkpm-label" />
            <div class="relative mt-1">
                <x-icon-mail class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                <x-text-input id="email" name="email" type="email" class="block w-full ps-9 pe-9" :value="old('email')" required autofocus autocomplete="username" />
                <x-icon-check id="email-valid-icon" data-email-check
                     class="pointer-events-none absolute end-3 top-1/2 hidden h-4 w-4 -translate-y-1/2 text-[var(--vc-accent)]" />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="mt-4">
            <x-input-label for="master-password" :value="__('Master Password')" class="zkpm-label" />
            <div x-data="{ show: false }" class="relative mt-1">
                <x-icon-lock class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
                <input id="master-password" type="password" required autocomplete="current-password"
                       :type="show ? 'text' : 'password'"
                       placeholder="••••••••••••"
                       data-capslock data-capslock-hint="login-caps-hint"
                       class="zkpm-input ps-9 pr-10">
                <button type="button"
                        @click="show = ! show"
                        :aria-label="show ? 'Hide' : 'Reveal'"
                        class="absolute end-2.5 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 transition dark:hover:text-slate-200 active:scale-90">
                    <x-icon-biometric x-show="! show" class="h-4 w-4" />
                </button>
            </div>
            <p id="login-caps-hint" data-capslock-panel
               class="mt-1.5 hidden items-center gap-1 text-[11px] font-semibold text-amber-600 dark:text-amber-400"
               role="status">
                <x-icon-verified class="h-3.5 w-3.5" />
                {{ __('Caps Lock is on') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('auth_hash_input')" />
        </div>

        <div class="mt-4 block">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800" name="remember">
                <span class="ms-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Remember me for 7 days') }}</span>
            </label>
        </div>

        <div id="login-error" class="mt-4 hidden rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800/60 p-3 text-sm text-red-700 dark:text-red-300" role="alert"></div>

        <div class="mt-6 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <a class="text-sm text-slate-600 dark:text-slate-400 underline hover:text-blue-600 dark:hover:text-blue-400 transition" href="{{ route('recovery') }}">
                {{ __('Forgot your password?') }}
            </a>

            <button type="submit" id="login-submit" class="zkpm-btn-primary min-w-[140px] active:scale-95 w-full sm:w-auto">
                <svg data-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span data-label>{{ __('Continue') }}</span>
            </button>
        </div>

        {{-- Register CTA --}}
        <div class="mt-6 flex items-center gap-3" aria-hidden="true">
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ __('New here?') }}</span>
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
        </div>

        <a href="{{ route('register') }}" id="login-register-link"
           class="zkpm-btn-secondary mt-4 w-full py-3 active:scale-95">
            <x-icon-plus class="h-4 w-4 text-[var(--vc-accent)]" />
            {{ __('Create Your Free Vault') }}
        </a>

        <p class="mt-5 flex items-center gap-1.5 text-[10px] text-slate-400 dark:text-slate-500">
            <x-icon-verified class="h-3 w-3 shrink-0" />
            {{ __('Login zero-knowledge note') }}
        </p>
    </form>
</x-guest-layout>