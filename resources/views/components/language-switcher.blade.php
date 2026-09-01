{{-- Language switcher: EN / العربية --}}
<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button type="button"
            @click="open = ! open"
            class="glass-btn inline-flex h-9 items-center justify-center gap-1.5 px-2.5 text-xs font-semibold text-slate-600 transition duration-200 ease-in-out hover:-translate-y-px hover:text-blue-600 active:translate-y-0 active:scale-95 dark:text-slate-400 dark:hover:border-blue-400/50 dark:hover:text-blue-300"
            title="{{ __('Language') }}"
            aria-label="{{ __('Language') }}">
        <x-icon-globe class="h-4 w-4" />
        <span class="hidden sm:inline uppercase">{{ app()->getLocale() === 'ar' ? 'ع' : 'EN' }}</span>
    </button>

    <div x-show="open"
         x-transition
         class="glass-panel absolute end-0 z-50 mt-2 w-36 overflow-hidden"
         style="display: none;">
        <button type="button"
                data-set-lang="en"
                @click="open = false"
                class="flex w-full items-center justify-between px-3.5 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700 dark:text-slate-300 dark:hover:bg-slate-700/60 dark:hover:text-white {{ app()->getLocale() === 'en' ? 'bg-blue-50 dark:bg-slate-700/40' : '' }}">
            <span>{{ __('English') }}</span>
            @if (app()->getLocale() === 'en')
                <x-icon-check class="h-3.5 w-3.5 text-blue-500" />
            @endif
        </button>
        <button type="button"
                data-set-lang="ar"
                @click="open = false"
                class="flex w-full items-center justify-between px-3.5 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-700 dark:text-slate-300 dark:hover:bg-slate-700/60 dark:hover:text-white {{ app()->getLocale() === 'ar' ? 'bg-blue-50 dark:bg-slate-700/40' : '' }}">
            <span class="font-arabic">{{ __('Arabic') }}</span>
            @if (app()->getLocale() === 'ar')
                <x-icon-check class="h-3.5 w-3.5 text-blue-500" />
            @endif
        </button>
    </div>
</div>
