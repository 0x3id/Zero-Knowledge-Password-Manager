<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="zkpm-page-header">{{ __('Active Sessions & Devices') }}</h2>
                <p class="zkpm-page-subtitle">{{ __('Manage and revoke active login sessions across your devices (SRS FR-8)') }}</p>
            </div>
            @if(count($sessions) > 1)
                <form method="POST" action="{{ route('sessions.destroy-others') }}" onsubmit="return confirm(@js(__('Revoke all others confirm')));">
                    @csrf
                    <button type="submit" class="zkpm-btn-danger">
                        <x-icon-logout class="w-4 h-4" />
                        {{ __('Revoke All Other Devices') }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status') === 'session-revoked')
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-sm font-medium text-emerald-800 dark:text-emerald-200">
                    {{ __('Session revoked success') }}
                </div>
            @elseif (session('status') === 'other-sessions-revoked')
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-sm font-medium text-emerald-800 dark:text-emerald-200">
                    {{ __('Other sessions revoked success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 text-sm font-medium text-red-800 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="glass-card p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700/80 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ __('Recognized device sessions') }} ({{ count($sessions) }})
                    </h3>
                    <x-security-badge type="info" :label="__('Server-enforced')" />
                </div>

                <div class="space-y-3">
                    @forelse ($sessions as $sess)
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border transition {{ $sess->is_current ? 'border-blue-300 dark:border-blue-700/60 bg-blue-50/40 dark:bg-blue-950/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30' }}">
                            <div class="flex items-start gap-3">
                                <div class="p-2.5 rounded-xl {{ $sess->is_current ? 'bg-blue-600 text-white shadow-glow-sm' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }} mt-0.5">
                                    <x-icon-devices class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $sess->device_name }}</h4>
                                        @if ($sess->is_current)
                                            <x-security-badge type="success" :label="__('Current Device')" />
                                        @endif
                                        @if ($sess->is_remember_me)
                                            <x-security-badge type="info" :label="__('7-Day Remembered')" />
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        <span>{{ __('IP') }}: <code class="font-mono text-slate-700 dark:text-slate-300">{{ $sess->ip_address }}</code></span>
                                        <span>•</span>
                                        <span>{{ __('Last active') }}: <strong class="text-slate-700 dark:text-slate-300">{{ \Carbon\Carbon::parse($sess->last_active_at)->diffForHumans() }}</strong></span>
                                        <span>•</span>
                                        <span>{{ __('Expires') }}: {{ \Carbon\Carbon::parse($sess->expires_at)->format('M d, H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                @if (! $sess->is_current)
                                    <form method="POST" action="{{ route('sessions.destroy', $sess->id) }}" onsubmit="return confirm(@js(__('Revoke device confirm')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 dark:bg-red-950/40 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/60 transition">
                                            {{ __('Revoke Device') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold px-2 py-1">{{ __('Active Now') }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('No active sessions') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="zkpm-security-banner">
                <x-icon-info class="w-5 h-5 text-[var(--vc-accent)] shrink-0" />
                <div>
                    <span class="font-bold">{{ __('Security architecture note') }}:</span>
                    {{ __('Session revoke description') }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
