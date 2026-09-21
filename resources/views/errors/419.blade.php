{{-- 419 · Page Expired — "Your session token expired."
     Clock icon (session credentials expire on a timer, like the app's own
     auto-lock design). Primary action is one click back to the previous page
     to retry the action. --}}
@extends('errors::layout')

@section('title', __('419 · Page Expired'))

@section('eye'){{ __('Session credentials') }} · 419 @endsection

@section('icon')
    <x-icon-clock class="h-9 w-9 text-[var(--vc-warn)]" />
@endsection

@section('headline', __('Your session token expired.'))

@section('message')
    {{ __('Session credentials expire by design. Sign in again and the vault will reopen.') }}
@endsection

@section('primaryCta')
    <a href="{{ url()->previous() }}" class="zkpm-btn-primary w-full sm:w-auto">
        <x-icon-refresh class="h-4 w-4" />
        <span>{{ __('Go Back and Retry') }}</span>
    </a>
@endsection

@section('secondaryCta')
    @auth
        <a href="{{ route('dashboard') }}" class="underline decoration-[var(--vc-border)] underline-offset-4 transition hover:text-[var(--vc-text)]">
            {{ __('Back to Dashboard') }}
        </a>
    @else
        <a href="{{ route('login') }}" class="underline decoration-[var(--vc-border)] underline-offset-4 transition hover:text-[var(--vc-text)]">
            {{ __('Back to login') }}
        </a>
    @endauth
@endsection