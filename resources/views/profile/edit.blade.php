<x-app-layout>
    <x-slot name="header">
        <h2 class="zkpm-page-header">{{ __('Account Settings & Security') }}</h2>
        <p class="zkpm-page-subtitle">
            {{ __('Configure account details, biometric passkeys, and authentication preferences') }}
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="glass-card p-6">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="glass-card p-6">
                <div class="max-w-xl">
                    @include('profile.partials.two-factor-status')
                </div>
            </div>

            <div class="glass-card p-6">
                <div class="max-w-2xl">
                    @include('profile.partials.manage-webauthn-form')
                </div>
            </div>

            <div class="glass-card p-6 border-red-200/50 dark:border-red-900/30">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
