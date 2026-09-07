<button {{ $attributes->merge(['type' => 'button', 'class' => 'glass-btn inline-flex items-center px-4 py-3 font-semibold text-xs text-slate-700 uppercase tracking-wider transition duration-200 ease-in-out hover:-translate-y-px hover:text-slate-900 active:translate-y-0 active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-slate-300 dark:hover:text-white disabled:opacity-25']) }}>
    {{ $slot }}
</button>
