<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="zkpm-welcome-title">
                    {{ __('Welcome back, :name', ['name' => $displayName]) }}
                </h1>
                <p class="zkpm-page-subtitle mt-1.5">
                    @if ($lastLogin)
                        {{ __('Last sign-in :when from :device', [
                            'when' => $lastLogin->created_at->diffForHumans(),
                            'device' => $lastLogin->device_info ?: __('an unknown device'),
                        ]) }}
                    @else
                        {{ __('Your vault is ready') }}
                    @endif
                </p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('vault') }}" class="zkpm-btn-secondary justify-center">
                    <x-icon-vault class="h-5 w-5" />
                    {{ __('Open Vault') }}
                </a>
                <a href="{{ route('generator') }}" class="zkpm-btn-primary justify-center">
                    <x-icon-generator class="h-5 w-5" />
                    {{ __('Generate Password') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Core counters --}}
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <x-icon-vault class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="zkpm-stat-label">{{ __('Vault Items') }}</p>
                        <p class="zkpm-stat-value">{{ number_format($counts['vault_items']) }}</p>
                    </div>
                </div>

                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl {{ $counts['weak_passwords'] > 0 ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' }} flex items-center justify-center">
                        <x-icon-breach class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="zkpm-stat-label">{{ __('Weak / Breached') }}</p>
                        @if ($counts['weak_passwords'] > 0)
                            <p class="zkpm-stat-value text-[var(--vc-warn)]">{{ number_format($counts['weak_passwords']) }}</p>
                        @else
                            <p class="zkpm-stat-value text-[var(--vc-accent)]">{{ $counts['weak_passwords'] }}</p>
                        @endif
                        <p class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider {{ $counts['weak_passwords'] > 0 ? 'text-[var(--vc-warn)]' : 'text-[var(--vc-text-dim)]' }}">
                            {{ __('Checked client-side') }}
                        </p>
                    </div>
                </div>

                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <x-icon-category class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="zkpm-stat-label">{{ __('Categories') }}</p>
                        <p class="zkpm-stat-value">{{ number_format($counts['categories']) }}</p>
                    </div>
                </div>

                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <x-icon-key class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="zkpm-stat-label">{{ __('Passwords Generated') }}</p>
                        <p class="zkpm-stat-value">{{ number_format($counts['generated_total']) }}</p>
                    </div>
                </div>

                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                        <x-icon-generator class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="zkpm-stat-label">{{ __('Generated This Week') }}</p>
                        <p class="zkpm-stat-value">{{ number_format($counts['generated_week']) }}</p>
                    </div>
                </div>

                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <x-icon-audit-log class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="zkpm-stat-label">{{ __('Audit Events') }}</p>
                        <p class="zkpm-stat-value">{{ number_format($counts['audit_week']) }}</p>
                    </div>
                </div>

                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                        <x-icon-devices class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="zkpm-stat-label">{{ __('Active Sessions') }}</p>
                        <p class="zkpm-stat-value">{{ number_format($counts['sessions']) }}</p>
                    </div>
                </div>

                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                        <x-icon-webauthn class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="zkpm-stat-label">{{ __('Passkeys') }}</p>
                        <p class="zkpm-stat-value">{{ number_format($counts['passkeys']) }}</p>
                    </div>
                </div>

                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl {{ $twoFactor['totp'] && $twoFactor['webauthn'] ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400' }} flex items-center justify-center">
                        <x-icon-webauthn class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="zkpm-stat-label">{{ __('Two-Factor Authentication') }}</p>
                        @if ($twoFactor['totp'] && $twoFactor['webauthn'])
                            <x-security-badge type="success" :label="__('TOTP + WebAuthn')" />
                        @elseif ($twoFactor['totp'])
                            <x-security-badge type="warning" :label="__('TOTP only')" />
                        @else
                            <x-security-badge type="danger" :label="__('Not fully protected')" />
                        @endif
                    </div>
                </div>

                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="shrink-0 h-11 w-11 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <x-icon-clock class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="zkpm-stat-label">{{ __('Member Since') }}</p>
                        <p class="zkpm-stat-value !text-base">{{ $memberSince->format('d M Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- Weekly activity chart + recent activity --}}
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="glass-card p-6 lg:col-span-2">
                    <div class="zkpm-card-heading">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <x-icon-audit-log class="w-4 h-4 text-[var(--vc-accent)]" />
                            <span>{{ __('Weekly Activity') }}</span>
                        </h3>
                        <div class="flex items-center gap-3 text-[10px] font-semibold text-slate-500 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-sm bg-blue-500/80 dark:bg-blue-400"></span>
                                {{ __('Item Created') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-sm bg-cyan-400/70"></span>
                                {{ __('Password Generated') }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 flex items-end justify-between gap-2 h-40" role="img" aria-label="{{ __('Weekly Activity') }}">
                        @foreach ($chart as $day)
                            <div class="flex-1 flex flex-col items-center gap-1.5" title="{{ $day['label'] }}: {{ $day['items'] }} / {{ $day['generated'] }}">
                                <div class="w-full flex items-end justify-center gap-1 h-28">
                                    <div class="w-2.5 sm:w-3 rounded-t bg-blue-500/80 dark:bg-blue-400 transition hover:opacity-80" style="height: {{ max(4, ($day['items'] / $chartMax) * 100) }}%"></div>
                                    <div class="w-2.5 sm:w-3 rounded-t bg-cyan-400/70 transition hover:opacity-80" style="height: {{ max(4, ($day['generated'] / $chartMax) * 100) }}%"></div>
                                </div>
                                <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="glass-card overflow-hidden p-6">
                    <div class="zkpm-card-heading">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <x-icon-clock class="w-4 h-4 text-[var(--vc-accent)]" />
                            <span>{{ __('Recent Activity') }}</span>
                        </h3>
                        <a href="{{ route('audit-logs.index') }}" class="text-[10px] font-semibold text-blue-600 hover:underline dark:text-blue-400">
                            {{ __('View All') }}
                        </a>
                    </div>

                    <ul class="mt-4 space-y-3">
                        @forelse ($recent as $log)
                            @php
                                $badges = [
                                    'login' => ['type' => 'success', 'label' => __('User Login')],
                                    'logout' => ['type' => 'info', 'label' => __('User Logout')],
                                    'vault_item_created' => ['type' => 'encrypted', 'label' => __('Item Created')],
                                    'vault_item_updated' => ['type' => 'info', 'label' => __('Item Updated')],
                                    'vault_item_deleted' => ['type' => 'danger', 'label' => __('Item Deleted')],
                                    'password_changed' => ['type' => 'warning', 'label' => __('Password Reset')],
                                    'password_generated' => ['type' => 'info', 'label' => __('Password Generated')],
                                    'webauthn_registered' => ['type' => 'success', 'label' => __('Passkey Added')],
                                    'webauthn_removed' => ['type' => 'warning', 'label' => __('Passkey Removed')],
                                    '2fa_totp_enabled' => ['type' => 'success', 'label' => __('TOTP 2FA Enabled')],
                                    'session_revoked' => ['type' => 'warning', 'label' => __('Session Revoked')],
                                    'recovery_used' => ['type' => 'danger', 'label' => __('Recovery Used')],
                                ];
                                $badge = $badges[$log->action_type] ?? ['type' => 'info', 'label' => $log->action_type];
                            @endphp
                            <li class="flex items-center justify-between gap-3">
                                <x-security-badge :type="$badge['type']" :label="$badge['label']" />
                                <span class="shrink-0 text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ $log->created_at->diffForHumans() }}</span>
                            </li>
                        @empty
                            <li class="py-6 text-center text-xs text-slate-500 dark:text-slate-400">
                                {{ __('No activity yet') }}
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <a href="{{ route('vault', ['add' => 1]) }}" class="glass-card group flex items-center justify-center gap-2.5 rounded-xl border p-5 transition hover:border-[color:var(--vc-accent)]/50 hover:shadow-glow-sm">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[var(--vc-accent-soft)] text-[var(--vc-accent)] transition group-hover:scale-110">
                        <x-icon-plus class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-bold text-[var(--vc-text)]">{{ __('Add Item') }}</span>
                </a>
                <a href="{{ route('generator') }}" class="glass-card group flex items-center justify-center gap-2.5 rounded-xl border p-5 transition hover:border-[color:var(--vc-accent)]/50 hover:shadow-glow-sm">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[var(--vc-accent-soft)] text-[var(--vc-accent)] transition group-hover:scale-110">
                        <x-icon-generator class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-bold text-[var(--vc-text)]">{{ __('Generate Password') }}</span>
                </a>
                <a href="{{ route('settings') }}" class="glass-card group flex items-center justify-center gap-2.5 rounded-xl border p-5 transition hover:border-[color:var(--vc-accent)]/50 hover:shadow-glow-sm">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[var(--vc-accent-soft)] text-[var(--vc-accent)] transition group-hover:scale-110">
                        <x-icon-security class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-bold text-[var(--vc-text)]">{{ __('Security Checkup') }}</span>
                </a>
                <a href="{{ route('audit-logs.index') }}" class="glass-card group flex items-center justify-center gap-2.5 rounded-xl border p-5 transition hover:border-[color:var(--vc-accent)]/50 hover:shadow-glow-sm">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[var(--vc-accent-soft)] text-[var(--vc-accent)] transition group-hover:scale-110">
                        <x-icon-audit-log class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-bold text-[var(--vc-text)]">{{ __('Audit Log') }}</span>
                </a>
                <a href="{{ route('sessions.index') }}" class="glass-card group flex items-center justify-center gap-2.5 rounded-xl border p-5 transition hover:border-[color:var(--vc-accent)]/50 hover:shadow-glow-sm">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[var(--vc-accent-soft)] text-[var(--vc-accent)] transition group-hover:scale-110">
                        <x-icon-devices class="h-5 w-5" />
                    </span>
                    <span class="text-sm font-bold text-[var(--vc-text)]">{{ __('Devices') }}</span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>