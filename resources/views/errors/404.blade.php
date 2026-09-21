{{-- 404 · Not Found — "This vault door doesn't exist."
     Vault-door icon with an X overlay badge: the requested location was never
     part of the vault's structure. --}}
@extends('errors::layout')

@section('title', __('404 · Not Found'))

@section('eye'){{ __('Vault index') }} · 404 @endsection

@section('icon')
    <x-icon-vault class="h-9 w-9 text-[var(--vc-text-dim)]" />
@endsection

@section('iconBadge')
    <x-icon-x class="h-3.5 w-3.5" />
@endsection

@section('headline', __('This vault door doesn’t exist.'))

@section('message')
    {{ __('The path you requested was never part of the vault’s structure. Check the address and try again.') }}
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