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

        <link rel="icon" href="{{ asset('favicon.svg') }}?v=2" type="image/svg+xml">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

        <title>{{ config('app.name', 'ZeroKnowledgePM') }} — {{ __('Vault') }}</title>

        {{-- No-flicker theme bootstrap: synchronous, runs BEFORE any <link>/CSS
            so the data-theme attribute is present before the first paint.
            App default = DARK. Preference: localStorage, else OS
            prefers-color-scheme, else dark. Sets data-theme on <html>
            (body doesn't exist yet) and mirrors Tailwind's `.dark` class. --}}
        <script nonce="{{ $cspNonce ?? '' }}">
            (function () {
                var theme = localStorage.getItem('zkpm_theme');
                if (theme !== 'dark' && theme !== 'light') {
                    theme = 'dark';
                }
                var root = document.documentElement;
                root.setAttribute('data-theme', theme);
                root.classList.toggle('dark', theme === 'dark');
                document.querySelector('meta[name="theme-color"]').setAttribute('content', theme === 'dark' ? '#0f1720' : '#f6f8fa');
                root.classList.add('js');
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space+grotesk:500,600,700|inter:400,500,600|jetbrains+mono:400,500|cairo:400,500,600,700|tajawal:400,500,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('components.i18n-script')
    </head>
    <body class="cyber-bg font-sans antialiased scrollbar-cyber min-h-screen" data-user-email="{{ auth()->user()->email ?? '' }}">
        <div x-data class="min-h-screen">
            @include('layouts.navigation')

            {{-- Content column: on desktop, offset by the fixed sidebar width
                 (64px mini rail / 256px expanded panel) using logical
                 `padding-inline-start` so it flips automatically in RTL.
                 On mobile/tablet the drawer overlays, so no offset. --}}
            <div :class="$store.sidebar.expanded ? 'lg:ps-64' : 'lg:ps-16'"
                 class="transition-[padding-inline-start] duration-300 ease-in-out">
                @isset($header)
                    <header class="border-b bg-[var(--vc-surface)] border-[var(--vc-border)]">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="animate-fade-in flex-1">
                    {{ $slot }}
                </main>

                <x-footer />
            </div>

            @include('components.lock-modal')
        </div>

        @include('components.toast-notifications')
    </body>
</html>
