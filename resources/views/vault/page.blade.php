<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="zkpm-page-header">
                    {{ __('Decrypted Vault') }}
                </h2>
                <p class="zkpm-page-subtitle">
                    {{ __('Client-side AES-256-GCM zero-knowledge encrypted credentials') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button"
                        id="manage-categories-btn"
                        class="zkpm-btn-secondary">
                    <x-icon-category class="w-4 h-4 text-slate-500" />
                    {{ __('Categories') }}
                </button>
                <button type="button"
                        id="vault-add"
                        class="zkpm-btn-primary">
                    <x-icon-plus class="w-4 h-4" />
                    {{ __('Add Item') }}
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @include('vault.unlock-form')
            @include('vault.index')
        </div>
    </div>

    @include('vault.create-edit-modal')
    @include('vault.category-manager-modal')

    @if (request()->has('add'))
        <script nonce="{{ $cspNonce ?? '' }}">
            window.addEventListener('zkpm:vault-ready', function () {
                var btn = document.getElementById('vault-add');
                if (btn) {
                    btn.click();
                }
            });
        </script>
    @endif
</x-app-layout>