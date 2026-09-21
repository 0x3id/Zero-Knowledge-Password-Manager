{{-- ═══════════════════════════════════════════════════════════════════════
    Vault Console — Error page shell.

    Standalone shell for the HTTP error views (401 / 403 / 404 / 419 / 429
    / 500 / 503), registered automatically by Laravel via the `errors::`
    namespace (resources/views/errors/{code}.blade.php — see
    Illuminate\Foundation\Exceptions\RegisterErrorViewPaths).

    Mirrors `resources/views/layouts/guest.blade.php` exactly so error pages
    honour the saved dark/light preference (data-theme bootstrap, default
    dark), the Arabic RTL flip, the fonts, the brand mark and the
    mobile-first / safe-area conventions of the rest of the console.

    Sections used by each error view:
      @section('title')        — head <title> suffix
      @section('icon')         — one Vault Console icon (existing icon language)
      @section('iconBadge')    — optional small overlay badge (reuses an
                                 existing icon, e.g. X on 404, clock on 503)
      @section('eye')          — mono eyebrow readout (status classification)
      @section('headline')     — the creative one-liner
      @section('message')      — the short supporting sentence
      @section('meta')         — optional mono meta chip (Retry-After readouts)
      @section('primaryCta')   — the single clear primary action
      @section('secondaryCta') — optional muted text link

    The status strip under the card is the signature live cipher-strip row:
    it reuses the exact markup/classes driven by resources/js/console.js
    (`#cipher-strip-line`) so the "system is still working — only this one
    path is blocked" signal is the same live element the app already uses.
    ═══════════════════════════════════════════════════════════════════════ --}}
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

        <title>@yield('title') — {{ config('app.name', 'ZeroKnowledgePM') }}</title>

        {{-- No-flicker theme bootstrap: synchronous, runs BEFORE any <link>/CSS
            so the data-theme attribute is present before the first paint.
            App default = DARK. Preference: localStorage, else dark.
            Sets data-theme on <html> (body doesn't exist yet) and
            mirrors Tailwind's `.dark` class. --}}
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
    <body class="cyber-bg font-sans antialiased scrollbar-cyber min-h-[100dvh]">
        <div class="relative flex min-h-[100dvh] flex-col items-center justify-center px-4 [padding-top:calc(env(safe-area-inset-top)+2rem)] [padding-bottom:calc(env(safe-area-inset-bottom)+2.5rem)]">

            {{-- Brand --}}
            <a href="{{ route('landing') }}" class="group flex flex-col items-center animate-slide-down"
               aria-label="{{ config('app.name', 'ZeroKnowledgePM') }}">
                <div class="relative h-14 w-14 rounded-2xl shadow-glass-sm transition group-hover:scale-105 group-hover:shadow-glass">
                    <x-brand-mark class="h-14 w-14 rounded-2xl" />
                    <span aria-hidden="true" class="absolute -bottom-1 -end-1 h-3.5 w-3.5 rounded-full border-2 border-[var(--vc-border)] bg-[var(--vc-accent)]"></span>
                </div>
                <span class="mt-2.5 text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                    ZeroKnowledge<span class="text-[var(--vc-accent)]">PM</span>
                </span>
            </a>

            {{-- Error card --}}
            <main class="glass-card relative mt-6 w-full animate-slide-up p-6 sm:max-w-md sm:p-8">
                <div class="text-center">

                    {{-- Icon tile --}}
                    <div class="relative mx-auto h-20 w-20">
                        <div class="flex h-20 w-20 items-center justify-center rounded-2xl border border-[var(--vc-border)] bg-[var(--vc-surface-2)] shadow-glass-sm">
                            @yield('icon')
                        </div>
                        @hasSection('iconBadge')
                            <span class="absolute -bottom-1.5 -end-1.5 flex h-8 w-8 items-center justify-center rounded-full border-2 border-[var(--vc-surface)] bg-[var(--vc-surface-2)] text-[var(--vc-text-dim)] shadow-glass-sm">
                                @yield('iconBadge')
                            </span>
                        @endif
                    </div>

                    {{-- Technical readout --}}
                    <p class="vc-eyebrow mt-5">@yield('eye')</p>

                    {{-- Creative headline --}}
                    <h1 class="zkpm-welcome-title mt-3">@yield('headline')</h1>

                    {{-- Short supporting sentence --}}
                    <p class="zkpm-page-subtitle mt-2 text-sm">@yield('message')</p>

                    {{-- Optional meta readout (Retry-After chips on 429 / 503) --}}
                    @hasSection('meta')
                        <div class="mt-4">@yield('meta')</div>
                    @endif

                    {{-- Actions --}}
                    <div class="mt-6 flex flex-col items-center gap-3">
                        @yield('primaryCta')
                        @hasSection('secondaryCta')
                            <p class="text-xs font-semibold" style="color: var(--vc-text-dim);">
                                @yield('secondaryCta')
                            </p>
                        @endif
                    </div>
                </div>
            </main>

            {{-- Live status strip: same live cipher-strip row the console uses
                 (driven by console.js via #cipher-strip-line) — the system is
                 still operational; only this one path is sealed/broken.
                 Mobile-safe: the static label drops below sm and the live line
                 truncates, so the strip never overflows 320px. --}}
            <div class="mt-6 flex w-full min-w-0 items-center justify-center gap-2.5 rounded-xl border border-[var(--vc-border)] bg-[var(--vc-surface)] px-3 py-2.5 shadow-glass-sm sm:max-w-md" role="status">
                <span class="vc-live-dot shrink-0" aria-hidden="true"></span>
                <span class="vc-eyebrow hidden shrink-0 sm:inline">{{ __('System status') }}</span>
                <span id="cipher-strip-line" class="vc-mono rtl-digits min-w-0 flex-1 truncate text-center text-[11px] sm:text-start"></span>
            </div>

            {{-- Theme + language (guest-shell controls) --}}
            <div class="mt-5 flex items-center justify-center gap-2">
                <x-theme-switcher />
                <x-language-switcher />
            </div>
        </div>
    </body>
</html>