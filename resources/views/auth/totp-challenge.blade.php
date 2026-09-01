<x-guest-layout>
    <div id="totp-error" class="mb-4 hidden rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800/60 p-3 text-sm text-red-700 dark:text-red-300"></div>

    <div class="mb-4">
        <div class="flex items-center gap-2 mb-1">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Two-factor authentication required') }}</h2>
            <x-security-badge type="success" :label="__('2FA')" />
        </div>
        <p class="text-sm text-slate-600 dark:text-slate-400">
            {{ __('Enter the 6-digit code from your authenticator app (Google Authenticator, Authy, etc.).') }}
        </p>
    </div>

    <form id="totp-challenge-form" class="mt-6">
        @csrf
        <div>
            <x-input-label for="totp-code" :value="__('Verification code')" class="zkpm-label" />
            <x-text-input id="totp-code"
                          type="text"
                          inputmode="numeric"
                          pattern="[0-9]{6}"
                          maxlength="6"
                          autocomplete="one-time-code"
                          class="mt-1 block w-full text-center text-2xl tracking-[0.5em]"
                          placeholder="000000"
                          required
                          autofocus />
        </div>

        <div class="mt-6 flex items-center justify-between gap-4">
            <a class="text-sm text-slate-600 dark:text-slate-400 underline hover:text-blue-600 dark:hover:text-blue-400 transition" href="{{ route('recovery') }}">
                {{ __('Lost your device?') }}
            </a>

            <button type="submit" id="totp-submit" class="zkpm-btn-primary disabled:opacity-50">
                {{ __('Verify') }}
            </button>
        </div>
    </form>
</x-guest-layout>
