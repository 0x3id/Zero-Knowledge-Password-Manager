<section>
    <header>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
            {{ __('Two-Factor Authentication (2FA)') }}
        </h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            {{ __('2FA status description') }}
        </p>
    </header>

    <div class="mt-4 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/30 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-emerald-600 text-white shadow-glow-sm">
                <x-icon-verified class="w-5 h-5 text-white" />
            </div>
            <div>
                <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('TOTP active title') }}</h4>
                <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('TOTP active description') }}</p>
            </div>
        </div>
        <x-security-badge type="success" :label="__('Enforced')" />
    </div>
</section>
