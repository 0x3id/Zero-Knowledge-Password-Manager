<button {{ $attributes->merge(['type' => 'submit', 'class' => 'glass-btn inline-flex items-center px-4 py-3 font-semibold text-xs text-red-700 uppercase tracking-wider transition duration-200 ease-in-out hover:-translate-y-px hover:border-red-400/80 hover:text-red-800 active:translate-y-0 active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-25 dark:text-red-300 dark:hover:border-red-300/60 dark:hover:text-red-200']) }}>
    {{ $slot }}
</button>
