{{-- 401 · Unauthorized — "This vault is sealed to you."
     The pin-tumbler key icon reads as "a valid credential is required".
     Primary action depends on auth state: dashboard for signed-in users,
     login for guests. --}}
@extends('errors::layout')

@section('title', __('401 · Unauthorized'))

@section('eye'){{ __('Vault sealed') }} · 401 @endsection

@section('icon')
    <x-icon-key class="h-9 w-9 text-[var(--vc-accent)]" />
@endsection

@section('headline', __('This vault is sealed to you.'))

@section('message')
    {{ __('Your session isn’t authenticated. Sign in with your credentials to reopen the vault.') }}
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