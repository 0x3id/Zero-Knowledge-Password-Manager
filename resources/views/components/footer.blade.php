<footer class="relative border-t border-[var(--vc-border)] bg-[var(--vc-surface)] [padding-bottom:env(safe-area-inset-bottom)]">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">

        <div class="grid gap-10 lg:grid-cols-4">

            {{-- Brand column --}}
            <div class="lg:col-span-1">
                <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                    <x-brand-mark class="h-9 w-9 rounded-xl shadow-glow-sm" />
                    <span class="font-bold text-base tracking-tight text-slate-900 dark:text-white">ZeroKnowledge<span class="text-[var(--vc-accent)]">PM</span></span>
                </a>

                {{-- Main goal tagline --}}
                <p class="mt-4 text-sm font-bold text-blue-600 dark:text-blue-300">
                    {{ __('Main Goal') }}
                </p>
                <p class="mt-1 text-xs font-semibold leading-relaxed text-slate-900 dark:text-white">
                    {{ __('All My Passwords in One Secure Place') }}
                </p>

                <p class="mt-3 text-xs leading-relaxed text-slate-500">
                    {{ __('Footer tagline') }}
                </p>

                {{-- Social links — real profiles --}}
                <div class="mt-5 flex items-center gap-5">
                    <a href="https://www.facebook.com/eid.yasser.eid.2025" target="_blank" rel="noopener noreferrer" title="Facebook" aria-label="Facebook" data-social="facebook"
                       class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-[color:var(--vc-accent)]/50 hover:text-[var(--vc-accent)] border-[var(--vc-border)] bg-[var(--vc-surface)] text-[var(--vc-text-dim)] hover:shadow-glow-sm hover:-translate-y-0.5">
                        <x-icon-social-facebook class="h-5 w-5" />
                    </a>
                    <a href="https://github.com/0x3id" target="_blank" rel="noopener noreferrer" title="GitHub" aria-label="GitHub" data-social="github"
                       class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-[color:var(--vc-accent)]/50 hover:text-[var(--vc-accent)] border-[var(--vc-border)] bg-[var(--vc-surface)] text-[var(--vc-text-dim)] hover:shadow-glow-sm hover:-translate-y-0.5">
                        <x-icon-social-github class="h-5 w-5" />
                    </a>
                    <a href="https://0x3id-portfolio.vercel.app" target="_blank" rel="noopener noreferrer" title="{{ __('Footer portfolio') }}" aria-label="{{ __('Footer portfolio') }}" data-social="portfolio"
                       class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-[color:var(--vc-accent)]/50 hover:text-[var(--vc-accent)] border-[var(--vc-border)] bg-[var(--vc-surface)] text-[var(--vc-text-dim)] hover:shadow-glow-sm hover:-translate-y-0.5">
                        <x-icon-social-portfolio class="h-5 w-5" />
                    </a>
                </div>
            </div>

            {{-- App navigation --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-slate-300">{{ __('Quick Access') }}</h3>
                <ul class="mt-4 space-y-2.5 text-xs">
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Dashboard') }}</a></li>
                        <li><a href="{{ route('vault') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Vault') }}</a></li>
                        <li><a href="{{ route('categories.index') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Categories') }}</a></li>
                        <li><a href="{{ route('generator') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Password Generator') }}</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Log in') }}</a></li>
                        <li><a href="{{ route('register') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Register') }}</a></li>
                        <li><a href="{{ route('landing') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Landing hero title') }}</a></li>
                    @endauth
                </ul>
            </div>

            {{-- Security & privacy --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-slate-300">{{ __('Footer security column') }}</h3>
                <ul class="mt-4 space-y-2.5 text-xs">
                    @auth
                        <li><a href="{{ route('settings') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Security Center') }}</a></li>
                        <li><a href="{{ route('audit-logs.index') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Activity Audit Log') }}</a></li>
                        <li><a href="{{ route('sessions.index') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Active Sessions') }}</a></li>
                        <li><a href="{{ route('webauthn.index') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Passkeys') }}</a></li>
                        <li><a href="{{ route('profile.edit') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Profile') }}</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('TOTP Two-Factor') }}</a></li>
                        <li><a href="{{ route('login') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('WebAuthn Passkeys') }}</a></li>
                        <li><a href="{{ route('login') }}" class="text-slate-500 transition hover:text-blue-600 dark:hover:text-blue-300">{{ __('Feature audit') }}</a></li>
                    @endauth
                </ul>
            </div>

            {{-- Technology & preferences --}}
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-slate-300">{{ __('Footer technology column') }}</h3>

                <ul class="mt-4 space-y-2 text-xs">
                    <li class="flex items-center gap-2 text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-[var(--vc-accent)]"></span><span class="font-mono rtl-digits">AES-256-GCM</span></li>
                    <li class="flex items-center gap-2 text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-[var(--vc-accent)]"></span><span class="font-mono rtl-digits">PBKDF2 · 600,000 Rounds</span></li>
                    <li class="flex items-center gap-2 text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-[var(--vc-accent)]"></span><span class="font-mono rtl-digits">TOTP · 2FA</span></li>
                    <li class="flex items-center gap-2 text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-[var(--vc-accent)]"></span><span class="font-mono rtl-digits">WebAuthn · Passkeys</span></li>
                    <li class="flex items-center gap-2 text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-[var(--vc-accent)]"></span>{{ __('Feature zero knowledge') }}</li>
                </ul>

                {{-- Preferences --}}
                <div class="mt-6 flex items-center gap-2">
                    <x-theme-switcher />
                    <x-language-switcher />
                </div>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-200 dark:border-slate-800/80 pt-6 text-xs text-slate-500 dark:text-slate-600 sm:flex-row">
            <div class="flex items-center gap-2">
                {{ __('Footer made by') }}
                <span class="font-semibold text-slate-900 dark:text-slate-300">Eid Yasser</span>
                <span class="mx-0.5 text-slate-400 dark:text-slate-700">·</span>
                <span>{{ __('Footer rights') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <x-icon-verified class="h-3.5 w-3.5 text-[var(--vc-accent)]" />
                <span class="rtl-digits">{{ config('app.name', 'ZeroKnowledgePM') }} © {{ date('Y') }} — {{ __('Main Goal') }}: {{ __('All My Passwords in One Secure Place') }}</span>
            </div>
        </div>
    </div>
</footer>