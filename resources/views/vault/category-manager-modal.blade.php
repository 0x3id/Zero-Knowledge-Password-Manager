{{-- Category management modal --}}
<div id="category-manager-modal" class="fixed inset-0 z-50 hidden flex overflow-y-auto bg-black/75 backdrop-blur-md px-4 py-6" role="dialog" aria-modal="true">
    <div class="w-full max-w-md m-auto glass-card rounded-2xl p-6 shadow-2xl space-y-4 animate-scale-in">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700/80 pb-3">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Manage Categories') }}</h3>
            <button type="button" id="category-manager-close" class="text-slate-400 hover:text-slate-700 dark:hover:text-white text-sm p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition" aria-label="{{ __('Close') }}">
                <x-icon-x class="w-4 h-4" />
            </button>
        </div>

        <form id="new-category-form" class="flex gap-2">
            <input type="text"
                   id="new-category-name"
                   required
                   maxlength="255"
                   placeholder="{{ __('New category name…') }}"
                   class="flex-1 zkpm-input text-xs">
            <button type="submit" class="zkpm-btn-primary shrink-0">
                {{ __('Add') }}
            </button>
        </form>

        <div class="space-y-2">
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Existing Categories') }}</label>
            <div id="category-manager-list" class="space-y-1.5 max-h-60 overflow-y-auto scrollbar-cyber pe-1">
                {{-- Populated by vault.js --}}
            </div>
        </div>
    </div>
</div>
