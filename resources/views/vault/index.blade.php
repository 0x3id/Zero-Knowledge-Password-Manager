{{-- Main vault content area (included from dashboard) --}}
<div id="vault-content" class="hidden space-y-6">
    {{-- Encryption status banner --}}
    <div class="zkpm-security-banner">
        <x-icon-verified class="w-5 h-5 text-[var(--vc-accent)] shrink-0 mt-0.5" />
        <div>
            <span class="font-bold">{{ __('Vault encryption active') }}:</span>
            {{ __('Client-side AES-256-GCM zero-knowledge encrypted credentials') }}
        </div>
        <x-security-badge type="encrypted" :label="__('End-to-End')" class="ms-auto shrink-0" />
    </div>

    {{-- Search & Category Filters --}}
    <div class="glass-card p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="relative flex-1 max-w-md">
            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none text-slate-400">
                <x-icon-search class="w-4 h-4" />
            </div>
            <input type="search"
                   id="vault-search"
                   placeholder="{{ __('Search title, username, or URL…') }}"
                   class="w-full ps-9 pe-4 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition">
        </div>

        <div id="category-filter-list" class="flex flex-wrap items-center gap-1.5">
            {{-- Populated by vault.js --}}
        </div>
    </div>

    {{-- Vault Items List --}}
    <div class="glass-card overflow-hidden p-6">
        <div class="zkpm-card-heading">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <x-icon-vault class="w-4 h-4 text-[var(--vc-accent)]" />
                <span>{{ __('Encrypted Vault Items') }}</span>
                <span id="vault-count" class="text-xs font-normal text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full"></span>
            </h3>
            <x-security-badge type="encrypted" :label="__('End-to-End')" />
        </div>

        <p id="vault-empty" class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
            {!! __('No vault items empty hint') !!}
        </p>

        <div id="vault-list" class="space-y-3">
            {{-- Rendered by vault.js --}}
        </div>
    </div>
</div>