<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="zkpm-page-header">{{ __('Activity Audit Log') }}</h2>
                <p class="zkpm-page-subtitle">{{ __('Privacy-preserving, metadata-only activity log (SRS FR-9)') }}</p>
            </div>
            <form method="GET" action="{{ route('audit-logs.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="action_type" onchange="this.form.submit()"
                        class="max-w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 focus:border-blue-500 focus:outline-none shadow-sm">
                    <option value="">{{ __('All Security Events') }}</option>
                    <option value="login" {{ $currentAction === 'login' ? 'selected' : '' }}>{{ __('User Login') }}</option>
                    <option value="logout" {{ $currentAction === 'logout' ? 'selected' : '' }}>{{ __('User Logout') }}</option>
                    <option value="vault_item_created" {{ $currentAction === 'vault_item_created' ? 'selected' : '' }}>{{ __('Item Created') }}</option>
                    <option value="vault_item_updated" {{ $currentAction === 'vault_item_updated' ? 'selected' : '' }}>{{ __('Item Updated') }}</option>
                    <option value="vault_item_deleted" {{ $currentAction === 'vault_item_deleted' ? 'selected' : '' }}>{{ __('Item Deleted') }}</option>
                    <option value="password_changed" {{ $currentAction === 'password_changed' ? 'selected' : '' }}>{{ __('Password Reset') }}</option>
                    <option value="password_generated" {{ $currentAction === 'password_generated' ? 'selected' : '' }}>{{ __('Password Generated') }}</option>
                    <option value="2fa_totp_enabled" {{ $currentAction === '2fa_totp_enabled' ? 'selected' : '' }}>{{ __('TOTP 2FA Enabled') }}</option>
                    <option value="webauthn_registered" {{ $currentAction === 'webauthn_registered' ? 'selected' : '' }}>{{ __('Passkey Added') }}</option>
                    <option value="webauthn_removed" {{ $currentAction === 'webauthn_removed' ? 'selected' : '' }}>{{ __('Passkey Removed') }}</option>
                    <option value="session_revoked" {{ $currentAction === 'session_revoked' ? 'selected' : '' }}>{{ __('Session Revoked') }}</option>
                    <option value="recovery_used" {{ $currentAction === 'recovery_used' ? 'selected' : '' }}>{{ __('Recovery Used') }}</option>
                </select>
                @if ($currentAction)
                    <a href="{{ route('audit-logs.index') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">{{ __('Clear filter') }}</a>
                @endif
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="zkpm-security-banner">
                <x-icon-verified class="w-5 h-5 text-[var(--vc-accent)] shrink-0 mt-0.5" />
                <div>
                    <span class="font-bold">{{ __('Zero-leakage privacy policy') }}:</span>
                    {!! __('Audit log privacy description') !!}
                </div>
            </div>

            <div class="glass-card overflow-hidden p-6 space-y-4">
                <div class="overflow-x-auto scrollbar-cyber">
                    <table class="zkpm-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Event Type') }}</th>
                                <th scope="col">{{ __('Device & Browser') }}</th>
                                <th scope="col">{{ __('IP Address') }}</th>
                                <th scope="col">{{ __('Timestamp') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="whitespace-nowrap">
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
                                        <x-security-badge :type="$badge['type']" :label="$badge['label']" />
                                    </td>
                                    <td class="text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                        {{ $log->device_info ?? __('Unknown Device') }}
                                    </td>
                                    <td class="font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        {{ $log->ip_address ?? '-' }}
                                    </td>
                                    <td class="text-slate-500 dark:text-slate-400 whitespace-nowrap" title="{{ $log->created_at }}">
                                        {{ \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i:s') }}
                                        <span class="text-[10px] text-slate-400">({{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }})</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">
                                        {{ __('No audit log records') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-700/80">
                    {{ $logs->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
