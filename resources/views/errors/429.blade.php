{{-- 429 · Too Many Requests — "Too many attempts — the vault needs a moment."
     Protective shield (security icon) in warn amber: a rate-limit guard, not a
     punishment. Meta chip surfaces the Retry-After window when the framework
     provides it (login/recovery throttling). --}}
@extends('errors::layout')

@section('title', __('429 · Too Many Requests'))

@section('eye'){{ __('Login guard') }} · 429 @endsection

@section('icon')
    <x-icon-security class="h-9 w-9 text-[var(--vc-warn)]" />
@endsection

@section('headline', __('Too many attempts — the vault needs a moment.'))

@section('message')
    {{ __('Too many sign-in attempts in a short window. This guard lifts automatically — try again shortly.') }}
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
            <span class="vc-eyebrow">{{ __('Try again in') }}</span>
            <span class="vc-mono rtl-digits text-[11px]">{{ $retryHuman }}</span>
        </span>
    @endif
@endsection

@section('primaryCta')
    @auth
        <a href="{{ route('dashboard') }}" class="zkpm-btn-primary w-full sm:w-auto">{{ __('Back to Dashboard') }}</a>
    @else
        <a href="{{ route('login') }}" class="zkpm-btn-primary w-full sm:w-auto">{{ __('Log in') }}</a>
    @endauth
@endsection

@section('secondaryCta')
    <a href="{{ route('landing') }}" class="underline decoration-[var(--vc-border)] underline-offset-4 transition hover:text-[var(--vc-text)]">
        {{ __('Back to homepage') }}
    </a>
@endsection