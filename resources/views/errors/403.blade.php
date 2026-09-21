{{-- 403 · Forbidden — "This vault is sealed to you."
     The pin-tumbler lock with engaged pins = an access/permissions boundary,
     not a broken app. Primary action depends on auth state. --}}
@extends('errors::layout')

@section('title', __('403 · Forbidden'))

@section('eye'){{ __('Vault sealed') }} · 403 @endsection

@section('icon')
    <x-icon-lock class="h-9 w-9 text-[var(--vc-text-dim)]" />
@endsection

@section('headline', __('This vault is sealed to you.'))

@section('message')
    {{ __('Your credentials open this vault, but not this section.') }}
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