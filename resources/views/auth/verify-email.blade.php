<x-guest-layout>
    <div class="mb-4 flex items-center gap-2">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Verify Your Email') }}</h2>
        <x-security-badge type="encrypted" :label="__('Zero-Knowledge')" />
    </div>

    {{-- Success / resend status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- Rate-limited cooldown notice --}}
    @if(session('status') === 'verification-rate-limited')
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50/60 p-4 text-sm text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200">
            <p class="flex items-center gap-2 font-medium">
                <x-icon-clock class="h-4 w-4 text-[var(--vc-warn)]" />
                {{ __('Too many requests') }}
            </p>
            <p class="mt-1">{{ __('Please wait before requesting another verification email.') }}</p>
        </div>
    @endif

    <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-800/60 dark:bg-blue-950/40">
        <div class="flex items-start gap-3">
            <x-icon-mail class="mt-0.5 h-5 w-5 shrink-0 text-[var(--vc-accent)]" />
            <div class="text-sm text-slate-700 dark:text-slate-300">
                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ __('We emailed you a verification link to :email.', ['email' => Auth::user()?->email]) }}</p>
                <p class="mt-1 text-slate-600 dark:text-slate-400">
                    {{ __('Confirm your address to activate your zero-knowledge vault. Until then, your vault remains locked for your own protection.') }}
                </p>
            </div>
        </div>
    </div>

    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50/60 p-4 text-sm text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200">
        <p class="flex items-center gap-2 font-medium">
            <x-icon-info class="h-4 w-4 text-[var(--vc-warn)]" />
            {{ __('Security notice') }}
        </p>
        <p class="mt-1">{{ __('The verification link expires in 60 minutes and can only be used once. Your master password and encryption key are never transmitted — this step only proves you own this email address.') }}</p>
    </div>

    <div class="mt-6 flex flex-col items-start gap-3">
        <form method="POST" action="{{ route('verification.send') }}" id="resend-form">
            @csrf
            <x-primary-button type="submit" id="resend-btn">{{ __('Resend Verification Email') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-slate-500 transition hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                {{ __('Log out and try again later') }}
            </button>
        </form>
    </div>

    {{-- Resend cooldown countdown --}}
    @php
        $cooldown = session('cooldown', 0);
    @endphp
    @if($cooldown > 0)
        <script nonce="{{ $cspNonce ?? '' }}">
            (function () {
                var btn = document.getElementById('resend-btn');
                if (!btn) return;
                var originalText = btn.textContent.trim();
                var remaining = {{ $cooldown }};
                btn.disabled = true;
                btn.textContent = originalText + ' (' + remaining + 's)';
                var interval = setInterval(function () {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(interval);
                        btn.disabled = false;
                        btn.textContent = originalText;
                    } else {
                        btn.textContent = originalText + ' (' + remaining + 's)';
                    }
                }, 1000);
            })();
        </script>
    @endif
</x-guest-layout>