<x-guest-layout>
    <div class="mb-4">
        <div class="flex items-center gap-2 mb-1">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Set up two-factor authentication') }}</h2>
            <x-security-badge type="success" :label="__('2FA')" />
        </div>
        <p class="text-sm text-slate-600 dark:text-slate-400">
            {{ __('Scan the QR code with Google Authenticator or Authy, then confirm with a code. This is required before you can open your vault.') }}
        </p>
    </div>

    <div id="totp-error" class="mb-4 hidden rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800/60 p-3 text-sm text-red-700 dark:text-red-300"></div>

    <div id="recovery-key-panel" class="mb-4 hidden rounded-xl border border-amber-300 dark:border-amber-700/60 bg-amber-50 dark:bg-amber-950/40 p-4">
        <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">{{ __('Recovery key save now') }}</p>
        <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
            {{ __('Recovery key save description') }}
        </p>
        <code id="recovery-key" class="mt-2 block break-all rounded-lg bg-amber-100 dark:bg-amber-900/60 px-3 py-2 text-sm font-mono text-amber-900 dark:text-amber-100"></code>
    </div>

    <button type="button" id="totp-generate" class="zkpm-btn-primary w-full">
        {{ __('Generate secret') }}
    </button>

    <div id="totp-secret-panel" class="mt-4 hidden space-y-4">
        <div class="relative flex justify-center rounded-xl bg-slate-100 dark:bg-slate-800/60 p-4 border border-slate-200 dark:border-slate-700">
            <div class="pointer-events-none absolute inset-1.5 rounded-lg border-2 border-dashed border-blue-300 dark:border-blue-700/50"></div>
            <canvas id="totp-qr" width="220" height="220" class="relative max-w-full rounded-lg bg-white h-auto"></canvas>
        </div>

        <div class="rounded-xl bg-slate-100 dark:bg-slate-800/60 p-3 border border-slate-200 dark:border-slate-700">
            <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('Manual entry secret') }}:</p>
            <div class="flex items-center gap-2 mt-1">
                <code id="totp-secret" class="block flex-1 break-all text-sm font-mono font-medium text-slate-900 dark:text-white"></code>
                <button type="button" id="totp-copy-secret"
                        class="shrink-0 rounded-lg p-1.5 text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 transition"
                        title="{{ __('Copy') }}">
                    <x-icon-copy class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div>
            <x-input-label for="totp-code" :value="__('Verification code')" class="zkpm-label" />
            <x-text-input id="totp-code"
                          type="text"
                          inputmode="numeric"
                          pattern="[0-9]{6}"
                          maxlength="6"
                          autocomplete="one-time-code"
                          class="mt-1 block w-full text-center text-2xl tracking-[0.5em]"
                          placeholder="000000" />
        </div>

        <div class="flex items-center justify-end">
            <button type="button" id="totp-confirm" class="zkpm-btn-primary disabled:opacity-50">
                {{ __('Confirm and enable') }}
            </button>
        </div>
    </div>
</x-guest-layout>
