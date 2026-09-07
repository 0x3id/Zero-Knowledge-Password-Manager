<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
      class="theme-transition {{ app()->getLocale() === 'ar' ? 'font-arabic' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">
        <meta name="theme-color" content="#fcfcfa">
        <meta name="description" content="{{ __('Landing meta description') }}">

        {{-- App icons: modern SVG mark, legacy ICO fallback, iOS touch icon, PWA manifest --}}
        <link rel="icon" href="{{ asset('favicon.svg') }}?v=2" type="image/svg+xml">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

        <title>{{ config('app.name', 'ZeroKnowledgePM') }} — {{ __('Zero-Knowledge Vault') }}</title>

        {{-- No-flicker theme bootstrap: synchronous, BEFORE any <link>/CSS.
            Dark is the product default. Preference: localStorage, else dark.
            Sets data-theme + .dark on <html> so the landing's dark:
            utilities resolve pre-paint. --}}
        <script nonce="{{ $cspNonce ?? '' }}">
            (function () {
                var root = document.documentElement;
                root.classList.add('js');
                var theme = localStorage.getItem('zkpm_theme');
                if (theme !== 'dark' && theme !== 'light') {
                    theme = 'dark';
                }
                root.setAttribute('data-theme', theme);
                root.classList.toggle('dark', theme === 'dark');
                document.querySelector('meta[name="theme-color"]').setAttribute('content', theme === 'dark' ? '#000000' : '#fcfcfa');
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|cairo:400,500,600,700|tajawal:400,500,700|jetbrains+mono:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('components.i18n-script')
    </head>

    <body class="cyber-bg text-slate-800 dark:text-slate-200 font-sans antialiased scrollbar-cyber min-h-screen overflow-x-hidden">

        {{-- Clean solid backdrop: no grids, patterns, or textures --}}
        <div aria-hidden="true"
             class="pointer-events-none fixed inset-0 z-0 bg-gradient-to-b from-blue-500/[0.04] via-transparent to-transparent">
        </div>

        <div class="relative z-10">

            {{-- ─────────────────────────── Navigation ─────────────────────────── --}}
            <nav class="sticky top-0 z-40 border-b border-white/40 bg-white/70 backdrop-blur-glass dark:border-white/10 dark:bg-black/50 [padding-top:env(safe-area-inset-top)]">
                <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                    <a href="{{ route('landing') }}" class="flex items-center gap-2.5 group">
                        <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-[var(--vc-accent)] to-[color-mix(in_srgb,var(--vc-accent)_55%,var(--vc-bg))] flex items-center justify-center shadow-glow-sm group-hover:shadow-glow transition">
                            <x-icon-vault class="h-5 w-5 text-[var(--vc-bg)]" />
                        </div>
                        <span class="font-bold text-base tracking-tight text-slate-900 dark:text-white">
                            ZeroKnowledge<span class="text-[var(--vc-accent)]">PM</span>
                        </span>
                    </a>

                    <div class="flex items-center gap-2">
                        <x-theme-switcher />
                        <x-language-switcher />
                        <a href="{{ route('login') }}"
                           class="hidden sm:inline-flex items-center px-3.5 py-2 text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-300 dark:hover:text-blue-200 transition">
                            {{ __('Log in') }}
                        </a>
                        <a href="{{ route('register') }}"
                           class="zkpm-btn-primary !py-2 !px-3.5">
                            {{ __('Register') }}
                        </a>
                    </div>
                </div>
            </nav>

            {{-- ───────────────────────────── Hero ───────────────────────────── --}}
            <section class="relative mx-auto max-w-6xl px-4 pt-16 pb-20 sm:px-6 sm:pt-24 sm:pb-28 text-center">

                <div class="relative inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-500/5 px-4 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-300 shadow-glass-sm animate-slide-down">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-400"></span>
                    </span>
                    {{ __('Landing hero badge') }}
                </div>

                {{-- Brand shield --}}
                <div class="relative mx-auto mt-10 h-40 w-40 animate-slide-up">
                    <div class="absolute inset-6 rounded-full bg-white dark:bg-black border border-blue-400/30 flex items-center justify-center shadow-glass mx-auto">
                        <x-brand-mark class="h-16 w-16 rounded-2xl shadow-glass-sm" />
                    </div>
                    <span aria-hidden="true" class="absolute bottom-1 end-1 h-5 w-5 rounded-full border-2 border-white bg-emerald-500 dark:border-black animate-pulse"></span>
                </div>

                {{-- Title with typing line --}}
                <h1 class="mx-auto mt-10 max-w-3xl text-[2.05rem] font-black leading-[1.15] tracking-tight text-slate-900 dark:text-white sm:text-6xl">
                    <span class="text-gradient-sheen">{{ __('Landing hero title') }}</span>
                </h1>

                <div class="mx-auto mt-4 flex h-8 items-center justify-center font-mono text-lg text-blue-600 dark:text-blue-400 sm:text-2xl rtl-digits" aria-live="polite">
                    <span id="landing-typed"></span><span class="ml-0.5 inline-block w-0.5 h-6 bg-blue-600 dark:bg-blue-400 animate-blink"></span>
                </div>

                <p class="mx-auto mt-6 max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400 sm:text-base">
                    {{ __('Landing hero subtitle') }}
                </p>

                <div class="mt-10 flex w-full flex-col items-stretch justify-center gap-3 sm:w-auto sm:flex-row sm:items-center">
                    <a href="{{ route('register') }}" class="zkpm-btn-primary !px-8 !py-3 !text-sm !rounded-2xl animate-float">
                        {{ __('Get Started') }}
                        <x-icon-chevron-right class="h-4 w-4 rtl:rotate-180" />
                    </a>
                    <a href="{{ route('login') }}" class="zkpm-btn-secondary !px-8 !py-3 !text-sm !rounded-2xl animate-float" style="animation-delay: 0.3s">
                        {{ __('Log in') }}
                    </a>
                </div>

                {{-- Floating security chips --}}
                <div class="pointer-events-none absolute inset-x-0 top-8 hidden lg:block" aria-hidden="true">
                    <span class="absolute start-[6%] top-16 rounded-lg border border-blue-500/20 bg-white/80 dark:bg-black/70 px-3 py-1.5 text-[10px] font-mono text-blue-600 dark:text-blue-400 animate-float">AES-256-GCM</span>
                    <span class="absolute end-[8%] top-24 rounded-lg border border-blue-500/20 bg-white/80 dark:bg-black/70 px-3 py-1.5 text-[10px] font-mono text-blue-600 dark:text-blue-400 animate-float" style="animation-delay: 1.2s">PBKDF2 · 600k</span>
                    <span class="absolute start-[10%] bottom-8 rounded-lg border border-emerald-500/20 bg-white/80 dark:bg-black/70 px-3 py-1.5 text-[10px] font-mono text-emerald-600 dark:text-emerald-400 animate-float" style="animation-delay: 2.1s">TOTP 2FA</span>
                    <span class="absolute end-[5%] bottom-16 rounded-lg border border-blue-500/20 bg-white/80 dark:bg-black/70 px-3 py-1.5 text-[10px] font-mono text-blue-600 dark:text-blue-400 animate-float" style="animation-delay: 0.6s">WebAuthn</span>
                </div>
            </section>

            {{-- ───────────────────────────── Stats ───────────────────────────── --}}
            <section class="relative mx-auto max-w-6xl px-4 pb-20 sm:px-6">
                <div class="glass-card rounded-3xl border border-blue-500/15 px-6 py-10 sm:px-10 reveal">
                    <div class="grid grid-cols-2 gap-6 sm:gap-8 sm:grid-cols-3 lg:grid-cols-6">

                        <div class="stat-item text-center">
                            <div class="text-3xl font-black text-slate-900 dark:text-white rtl-digits" data-count="{{ $stats['users'] }}">0</div>
                            <div class="mt-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ __('Stats users') }}</div>
                        </div>

                        <div class="stat-item text-center">
                            <div class="text-3xl font-black text-blue-600 dark:text-blue-400 rtl-digits" data-count="{{ $stats['passwordsStored'] }}">0</div>
                            <div class="mt-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ __('Stats passwords') }}</div>
                        </div>

                        <div class="stat-item text-center">
                            <div class="text-3xl font-black text-blue-600 dark:text-blue-400 rtl-digits" data-count="{{ $stats['passwordsGenerated'] }}">0</div>
                            <div class="mt-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ __('Stats generated') }}</div>
                        </div>

                        <div class="stat-item text-center">
                            <div class="text-3xl font-black text-slate-900 dark:text-white rtl-digits" data-count="{{ $stats['categories'] }}">0</div>
                            <div class="mt-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ __('Stats categories') }}</div>
                        </div>

                        <div class="stat-item text-center">
                            <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 rtl-digits" data-count="{{ $stats['twoFactorUsers'] }}">0</div>
                            <div class="mt-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ __('Stats 2fa') }}</div>
                        </div>

                        <div class="stat-item text-center">
                            <div class="text-3xl font-black text-slate-900 dark:text-white rtl-digits" data-count="{{ $stats['auditEvents'] }}">0</div>
                            <div class="mt-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ __('Stats audit') }}</div>
                        </div>

                    </div>
                </div>
            </section>

            {{-- ─────────────────────────── Features ─────────────────────────── --}}
            <section class="relative mx-auto max-w-6xl px-4 pb-24 sm:px-6">
                <div class="text-center reveal">
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white sm:text-4xl">{{ __('Landing features title') }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-slate-500 dark:text-slate-400 sm:text-base">{{ __('Landing features subtitle') }}</p>
                </div>

                <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">

                    @php
                        $features = [
                            ['icon' => 'icons.security', 'title' => 'Feature zero knowledge', 'desc' => 'Feature zero knowledge desc'],
                            ['icon' => 'icons.lock', 'title' => 'Feature aes', 'desc' => 'Feature aes desc'],
                            ['icon' => 'icons.recovery-key', 'title' => 'Feature pbkdf2', 'desc' => 'Feature pbkdf2 desc'],
                            ['icon' => 'icons.devices', 'title' => 'Feature totp', 'desc' => 'Feature totp desc'],
                            ['icon' => 'icons.webauthn', 'title' => 'Feature passkey', 'desc' => 'Feature passkey desc'],
                            ['icon' => 'icons.breach', 'title' => 'Feature breach', 'desc' => 'Feature breach desc'],
                            ['icon' => 'icons.audit-log', 'title' => 'Feature audit', 'desc' => 'Feature audit desc'],
                            ['icon' => 'icons.generator', 'title' => 'Feature generator', 'desc' => 'Feature generator desc'],
                        ];
                    @endphp

                    @foreach ($features as $i => $feature)
                        <div class="group glass-card rounded-2xl p-6 transition duration-200 ease-in-out hover:-translate-y-1 hover:shadow-glass reveal" style="transition-delay: {{ $i * 40 }}ms">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-[color:var(--vc-accent)]/20 bg-[var(--vc-accent-soft)] text-[var(--vc-accent)] transition group-hover:border-[color:var(--vc-accent)]/40">
                                <x-dynamic-component :component="$feature['icon']" class="h-6 w-6" />
                            </div>
                            <h3 class="mt-4 text-sm font-bold text-slate-900 dark:text-white">{{ __($feature['title']) }}</h3>
                            <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __($feature['desc']) }}</p>
                        </div>
                    @endforeach

                </div>
            </section>

            {{-- ─────────────────────── How it works ─────────────────────── --}}
            <section class="relative border-y border-slate-200/60 bg-white/40 py-20 dark:border-white/[0.06] dark:bg-white/[0.02]">
                <div class="mx-auto max-w-6xl px-4 sm:px-6">
                    <div class="text-center reveal">
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white sm:text-4xl">{{ __('Landing pipeline title') }}</h2>
                    </div>

                    <div class="mt-14 grid gap-8 lg:grid-cols-3">
                        @foreach ([
                            ['step' => '01', 'title' => 'Step 1', 'desc' => 'Step 1 desc'],
                            ['step' => '02', 'title' => 'Step 2', 'desc' => 'Step 2 desc'],
                            ['step' => '03', 'title' => 'Step 3', 'desc' => 'Step 3 desc'],
                        ] as $i => $step)
                            <div class="relative reveal" style="transition-delay: {{ $i * 120 }}ms">
                                <div class="flex items-center gap-4">
                                    <div class="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-blue-400/30 bg-white dark:bg-black font-mono text-lg font-bold text-blue-600 dark:text-blue-400 shadow-glass-sm">
                                        {{ $step['step'] }}
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __($step['title']) }}</h3>
                                        <p class="mt-1.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __($step['desc']) }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Ciphertext line --}}
                    <div class="mx-auto mt-16 max-w-3xl reveal">
                        <div class="flex flex-wrap items-center justify-center gap-2.5 sm:gap-3 font-mono text-[10px] sm:text-[11px] rtl-digits">
                            <span class="rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-black px-3 py-2 text-slate-600 dark:text-slate-500">{{ __('Step 1') }}</span>
                            <x-icon-chevron-right class="h-4 w-4 text-[var(--vc-accent)] rtl:rotate-180" />
                            <span class="rounded-lg border border-blue-500/30 bg-blue-500/5 px-3 py-2 text-[var(--vc-accent)] shadow-glass-sm">AES-256-GCM</span>
                            <x-icon-chevron-right class="h-4 w-4 text-[var(--vc-accent)] rtl:rotate-180" />
                            <span class="rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-black px-3 py-2 text-slate-600 dark:text-slate-500">{{ __('Step 3') }}</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ───────────────────────────── CTA ───────────────────────────── --}}
            <section class="relative mx-auto max-w-6xl px-4 py-24 sm:px-6">
                <div class="relative overflow-hidden rounded-3xl glass-card p-10 text-center sm:p-16 reveal">
                    <div aria-hidden="true" class="pointer-events-none absolute -top-24 start-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-blue-500/10 blur-3xl"></div>

                    <h2 class="relative text-3xl font-black text-slate-900 dark:text-white sm:text-4xl">{{ __('Landing cta title') }}</h2>
                    <p class="relative mx-auto mt-3 max-w-lg text-sm text-slate-500 dark:text-slate-400 sm:text-base">{{ __('Landing cta subtitle') }}</p>
                    <div class="relative mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="zkpm-btn-primary !px-10 !py-3.5 !text-sm !rounded-2xl">
                            {{ __('Create Your Free Vault') }}
                        </a>
                        <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-300 dark:hover:text-blue-200 transition">{{ __('Log in') }}</a>
                    </div>
                </div>
            </section>

            {{-- ──────────────────────────── Footer ──────────────────────────── --}}
            <x-footer />

        </div>
    </body>
</html>
