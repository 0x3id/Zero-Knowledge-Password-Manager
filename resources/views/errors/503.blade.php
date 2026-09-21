{{-- 503 · Service Unavailable — "The vault is temporarily sealed for maintenance."
     Lock icon + clock overlay badge: sealed for a scheduled maintenance
     window. Meta chip surfaces the estimated return (Retry-After) when the
     maintenance schedule sets one (`php artisan down --retry=...`). --}}
@extends('errors::layout')

@section('title', __('503 · Service Unavailable'))

@section('eye'){{ __('Maintenance window') }} · 503 @endsection

@section('icon')
    <x-icon-lock class="h-9 w-9 text-[var(--vc-warn)]" />
@endsection

@section('iconBadge')
    <x-icon-clock class="h-3.5 w-3.5" />
@endsection

@section('headline', __('The vault is temporarily sealed for maintenance.'))

@section('message')
    {{ __('We’re servicing the vault and it will reopen shortly.') }}
@endsection

@section('meta')
    @php
        $retryAfter = isset($exception) && method_exists($exception, 'getHeaders')
            ? ($exception->getHeaders()['Retry-After'] ?? null)
            : null;

        $retryHuman = null;
        if ($retryAfter !== null) {
            $seconds = max(1, (int) $retryAfter);
            $retryHuman = $seconds < 60
                ? __('About :count seconds', ['count' => $seconds])
                : __('About :count minutes', ['count' => ceil($seconds / 60)]);
        }
    @endphp
    @if ($retryHuman)
        <span class="inline-flex items-center gap-2 rounded-full border border-[var(--vc-border)] bg-[var(--vc-surface-2)] px-3.5 py-1.5 shadow-glass-sm">
            <x-icon-clock class="h-3.5 w-3.5 text-[var(--vc-warn)]" />
            <span class="vc-eyebrow">{{ __('Estimated return') }}</span>
            <span class="vc-mono rtl-digits text-[11px]">{{ $retryHuman }}</span>
        </span>
    @endif
@endsection

@section('primaryCta')
    <a href="{{ url()->current() }}" class="zkpm-btn-primary w-full sm:w-auto">{{ __('Check Again') }}</a>
@endsection

@section('secondaryCta')
    <a href="https://github.com/0x3id" rel="noopener noreferrer" target="_blank"
       class="underline decoration-[var(--vc-border)] underline-offset-4 transition hover:text-[var(--vc-text)]">
        {{ __('Contact support') }}
    </a>
@endsection