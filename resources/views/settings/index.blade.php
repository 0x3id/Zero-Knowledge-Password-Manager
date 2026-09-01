<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="zkpm-page-header">{{ __('Security Center') }}</h2>
            <p class="zkpm-page-subtitle">{{ __('Security overview description') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Posture banner --}}
            <div class="zkpm-security-banner">
                <x-icon-verified class="w-5 h-5 text-[var(--vc-accent)] shrink-0 mt-0.5" />
                <div>
                    <span class="font-bold">{{ __('Security posture') }}:</span>
                    {{ __('TOTP 2FA') }} — {{ __('Enforced') }} • AES-256-GCM • {{ __('Zero-Knowledge') }}
                </div>
                <x-security-badge type="encrypted" :label="__('Protection enabled')" class="ms-auto shrink-0" />
            </div>

            {{-- Quick actions --}}
            <div class="glass-card p-5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Quick Actions') }}</h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <a href="{{ route('dashboard') }}" class="zkpm-stat-card hover:no-underline">
                        <span class="zkpm-stat-icon"><x-icon-vault class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-xs font-bold text-slate-800 dark:text-white">{{ __('Open Vault') }}</span>
                            <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Add Item') }} / {{ __('Categories') }}</span>
                        </span>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="zkpm-stat-card hover:no-underline">
                        <span class="zkpm-stat-icon"><x-icon-webauthn class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-xs font-bold text-slate-800 dark:text-white">{{ __('Manage Passkeys') }}</span>
                            <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('WebAuthn & Biometric Passkeys') }}</span>
                        </span>
                    </a>
                    <a href="{{ route('sessions.index') }}" class="zkpm-stat-card hover:no-underline">
                        <span class="zkpm-stat-icon"><x-icon-devices class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-xs font-bold text-slate-800 dark:text-white">{{ __('View Sessions') }}</span>
                            <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Revoke Device') }} / {{ __('Revoke All Other Devices') }}</span>
                        </span>
                    </a>
                    <a href="{{ route('audit-logs.index') }}" class="zkpm-stat-card hover:no-underline">
                        <span class="zkpm-stat-icon"><x-icon-audit-log class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-xs font-bold text-slate-800 dark:text-white">{{ __('View Audit Log') }}</span>
                            <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('All Security Events') }}</span>
                        </span>
                    </a>
                </div>
            </div>

            {{-- Vault & Cryptography --}}
            <div class="glass-card p-6">
                <div class="zkpm-card-heading">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <x-icon-vault class="w-4 h-4 text-[var(--vc-accent)]" />
                        {{ __('Vault & Cryptography') }}
                    </h3>
                    <x-encryption-badge />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="zkpm-stat-card">
                        <span class="zkpm-stat-icon"><x-icon-lock class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-lg font-bold text-slate-900 dark:text-white">{{ $vault_item_count }}</span>
                            <span class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Encrypted Items') }}</span>
                        </span>
                    </div>
                    <div class="zkpm-stat-card">
                        <span class="zkpm-stat-icon"><x-icon-category class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-lg font-bold text-slate-900 dark:text-white">{{ $category_count }}</span>
                            <span class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Custom Categories') }}</span>
                        </span>
                    </div>
                    <div class="zkpm-stat-card">
                        <span class="zkpm-stat-icon"><x-icon-verified class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-lg font-bold text-slate-900 dark:text-white">AES-256-GCM</span>
                            <span class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Encryption') }}</span>
                        </span>
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">{{ __('Items stored encrypted with AES-256-GCM. The server only ever sees ciphertext.') }}</p>
            </div>

            {{-- Authentication & Recovery --}}
            <div class="glass-card p-6">
                <div class="zkpm-card-heading">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <x-icon-verified class="w-4 h-4 text-[var(--vc-accent)]" />
                        {{ __('Authentication & Recovery') }}
                    </h3>
                    <x-security-badge type="success" :label="__('Enforced')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="zkpm-stat-card">
                        <span class="zkpm-stat-icon"><x-icon-webauthn class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ __('TOTP 2FA') }}</span>
                            <span class="block text-[10px] font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">{{ $is_totp_complete ? __('Protection enabled') : __('Not enrolled') }}</span>
                        </span>
                    </div>
                    <div class="zkpm-stat-card">
                        <span class="zkpm-stat-icon"><x-icon-webauthn class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-lg font-bold text-slate-900 dark:text-white">{{ $passkey_count }}</span>
                            <span class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Passkeys Enrolled') }}</span>
                        </span>
                    </div>
                    <div class="zkpm-stat-card">
                        <span class="zkpm-stat-icon"><x-icon-recovery-key class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ __('Recovery key ready') }}</span>
                            <span class="block text-[10px] uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Configured') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Device & Session Health --}}
            <div class="glass-card p-6">
                <div class="zkpm-card-heading">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <x-icon-devices class="w-4 h-4 text-[var(--vc-accent)]" />
                        {{ __('Device & Session Health') }}
                    </h3>
                    <x-security-badge type="info" :label="__('Server-enforced')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="zkpm-stat-card">
                        <span class="zkpm-stat-icon"><x-icon-devices class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-lg font-bold text-slate-900 dark:text-white">{{ $active_sessions }}</span>
                            <span class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Active Sessions') }}</span>
                        </span>
                    </div>
                    <div class="zkpm-stat-card">
                        <span class="zkpm-stat-icon"><x-icon-audit-log class="w-5 h-5" /></span>
                        <span>
                            <span class="block text-lg font-bold text-slate-900 dark:text-white">{{ $audit_log_count }}</span>
                            <span class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Audit Log Records') }}</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Account management link --}}
            <div class="glass-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="zkpm-stat-icon"><x-icon-user class="w-5 h-5" /></div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Profile Information') }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Manage your profile, passkeys, and authentication preferences.') }}</p>
                    </div>
                </div>
                <a href="{{ route('profile.edit') }}" class="zkpm-btn-secondary shrink-0">{{ __('Review account settings') }}</a>
            </div>

        </div>
    </div>
</x-app-layout>