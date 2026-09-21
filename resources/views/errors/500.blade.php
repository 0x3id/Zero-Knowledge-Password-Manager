{{-- 500 · Server Error — "Something jammed in the mechanism."
     Vault-door icon in danger red: an internal fault framed as a mechanical
     vault malfunction. This view NEVER references $exception — the copy is
     identical regardless of what actually failed, in every environment. --}}
@extends('errors::layout')

@section('title', __('500 · Server Error'))

@section('eye'){{ __('Vault mechanism') }} · 500 @endsection

@section('icon')
    <x-icon-vault class="h-9 w-9 text-[var(--vc-danger)]" />
@endsection

@section('headline', __('Something jammed in the mechanism.'))

@section('message')
    {{ __('The vault’s mechanism hit an internal fault. Nothing was exposed — try again in a moment.') }}
@endsection

@section('primaryCta')
    @auth
        <a href="{{ route('dashboard') }}" class="zkpm-btn-primary w-full sm:w-auto">{{ __('Back to Dashboard') }}</a>
    @else
        <a href="{{ route('login') }}" class="zkpm-btn-primary w-full sm:w-auto">{{ __('Log in') }}</a>
    @endauth
@endsection

@section('secondaryCta')
    <a href="https://github.com/0x3id" rel="noopener noreferrer" target="_blank"
       class="underline decoration-[var(--vc-border)] underline-offset-4 transition hover:text-[var(--vc-text)]">
        {{ __('Contact support') }}
    </a>
@endsection