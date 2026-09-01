<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      class="theme-transition {{ app()->getLocale() === 'ar' ? 'font-arabic' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="dark light">
        <meta name="theme-color" content="#0f1720">

        {{-- App icons: modern SVG mark, legacy ICO fallback, iOS touch icon, PWA manifest --}}
        <link rel="icon" href="{{ asset('favicon.svg') }}?v=2" type="image/svg+xml">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

        <title>{{ config('app.name', 'ZeroKnowledgePM') }} — {{ __('Zero-Knowledge Vault') }}</title>

        {{-- No-flicker theme bootstrap: resolved before first paint.
            Vault Console defaults to DARK (data-theme on <html>) and stays
            in sync with Tailwind's `.dark` class. --}}
        <script nonce="{{ $cspNonce ?? '' }}">
            (function () {
                var theme = localStorage.getItem('zkpm_theme') || 'dark';
                var root = document.documentElement;
                root.setAttribute('data-theme', theme);
                root.classList.toggle('dark', theme === 'dark');
                document.querySelector('meta[name="theme-color"]').setAttribute('content', theme === 'dark' ? '#0f1720' : '#f6f8fa');
            })();
        </script>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('components.i18n-script')
    </head>
    <body class="cyber-bg font-sans antialiased scrollbar-cyber min-h-screen">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative">

            {{-- Brand --}}
            <div class="relative animate-slide-down">
                <a href="{{ route('login') }}" class="flex flex-col items-center group">
                    <div class="relative h-16 w-16 rounded-2xl shadow-glass-sm transition group-hover:scale-105 group-hover:shadow-glass">
                        <x-brand-mark class="h-16 w-16 rounded-2xl" />
                        <span aria-hidden="true" class="absolute -bottom-1 -end-1 h-4 w-4 rounded-full border-2 border-[var(--vc-border)] bg-emerald-500"></span>
                    </div>
                    <span class="mt-3 font-bold text-xl tracking-tight text-slate-900 dark:text-white">
                        ZeroKnowledge<span class="text-[var(--vc-accent)]">PM</span>
                    </span>
                    <span class="mt-0.5 text-[10px] uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">
                        {{ __('Zero-Knowledge Vault') }}
                    </span>
                </a>
            </div>

            {{-- Guest card --}}
            <div class="relative w-full sm:max-w-md mt-6 px-4 sm:px-0 animate-slide-up">
                <div class="glass-card p-6 sm:p-8">
                    {{ $slot }}
                </div>

                <div class="mt-4 flex items-center justify-center gap-2">
                    <x-theme-switcher />
                    <x-language-switcher />
                </div>
            </div>

            {{-- Footer security note --}}
            <p class="relative mt-8 mb-4 flex items-center gap-1.5 text-[10px] text-slate-400 dark:text-slate-600">
                <x-icon-verified class="h-3 w-3" />
                {{ __('Security footer tagline') }}
            </p>
        </div>

        <x-footer />

        @include('components.toast-notifications')
    </body>
</html>