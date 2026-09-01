{{-- Global toast notification stack (RTL-safe, auto-dismiss with progress bar) --}}
<div class="pointer-events-none fixed z-[100] flex w-full max-w-sm flex-col gap-2 px-4 start-0 top-4 sm:px-0 sm:top-auto sm:bottom-4 sm:start-4"
     x-data
     x-show="$store.toasts.items.length > 0"
     role="status"
     aria-live="polite">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="pointer-events-auto relative flex items-start gap-3 overflow-hidden rounded-xl border p-3.5 shadow-glass-sm backdrop-blur-glass animate-slide-up"
             :class="{
                 'border-emerald-200 bg-emerald-50/90 dark:border-emerald-800/70 dark:bg-emerald-950/80': toast.type === 'success',
                 'border-red-200 bg-red-50/90 dark:border-red-800/70 dark:bg-red-950/80': toast.type === 'error',
                 'border-amber-200 bg-amber-50/90 dark:border-amber-800/70 dark:bg-amber-950/80': toast.type === 'warning',
                 'border-blue-200 bg-blue-50/90 dark:border-blue-800/70 dark:bg-blue-950/80': toast.type === 'info',
             }">
            <span class="mt-0.5 shrink-0"
                  :class="{
                      'text-emerald-600 dark:text-emerald-400': toast.type === 'success',
                      'text-red-600 dark:text-red-400': toast.type === 'error',
                      'text-amber-600 dark:text-amber-400': toast.type === 'warning',
                      'text-blue-600 dark:text-blue-400': toast.type === 'info',
                  }">
                <x-icon-check class="h-4 w-4" x-show="toast.type === 'success'" />
                <x-icon-info class="h-4 w-4" x-show="toast.type === 'error'" style="display:none;" />
                <x-icon-breach class="h-4 w-4" x-show="toast.type === 'warning'" style="display:none;" />
                <x-icon-info class="h-4 w-4" x-show="toast.type === 'info'" style="display:none;" />
            </span>
            <p class="flex-1 text-xs font-medium leading-relaxed"
               :class="{
                   'text-emerald-800 dark:text-emerald-200': toast.type === 'success',
                   'text-red-800 dark:text-red-200': toast.type === 'error',
                   'text-amber-800 dark:text-amber-200': toast.type === 'warning',
                   'text-blue-800 dark:text-blue-200': toast.type === 'info',
               }"
               x-text="toast.message"></p>
            <button type="button"
                    @click="$store.toasts.dismiss(toast.id)"
                    class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200"
                    aria-label="{{ __('Dismiss') }}">
                <x-icon-x class="h-3.5 w-3.5" />
            </button>
            {{-- Auto-dismiss progress indicator --}}
            <span aria-hidden="true"
                  class="toast-progress pointer-events-none absolute inset-x-0 bottom-0 h-0.5 bg-current opacity-40"
                  :style="`animation-duration: ${toast.duration}ms`"></span>
        </div>
    </template>
</div>