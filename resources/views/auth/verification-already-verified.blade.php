<x-guest-layout>
    <div class="mb-4 flex items-center gap-2">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Verify Your Email') }}</h2>
        <x-security-badge type="encrypted" :label="__('Zero-Knowledge')" />
    </div>

    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-6 dark:border-emerald-800/60 dark:bg-emerald-950/40">
        <div class="flex items-start gap-3">
            <x-icon-verified class="mt-0.5 h-5 w-5 shrink-0 text-[var(--vc-accent)]" />
            <div class="text-sm text-slate-700 dark:text-slate-300">
                <p class="font-semibold text-slate-800 dark:text-slate-100">
                    {{ __('Your email address :email has already been verified.', ['email' => $email]) }}
                </p>
                <p class="mt-1 text-slate-600 dark:text-slate-400">
                    {{ __('You can now set up your two-factor authentication and access your zero-knowledge vault.') }}
                </p>
                <p class="mt-3">
                    <a href="{{ route('totp.setup') }}" class="font-medium text-[var(--vc-accent)] hover:underline">
                        {{ __('Go to TOTP setup') }}
                    </a>
                </p>
            </div>
        </div>
    </div>

    <div class="mt-6 flex flex-col items-start gap-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-slate-500 transition hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                {{ __('Log out and try again later') }}
            </button>
        </form>
    </div>
</x-guest-layout>