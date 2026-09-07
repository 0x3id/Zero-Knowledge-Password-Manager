<button {{ $attributes->merge(['type' => 'submit', 'class' => 'glass-btn inline-flex items-center px-4 py-3 font-bold text-xs text-blue-700 uppercase tracking-wider transition duration-200 ease-in-out hover:-translate-y-px hover:border-blue-400/80 hover:text-blue-800 active:translate-y-0 active:scale-[0.97] focus:outline-none focus:ring-2 focus:ring-blue-400/40 disabled:opacity-25 dark:text-blue-300 dark:hover:border-blue-300/60 dark:hover:text-blue-200']) }}>
    {{ $slot }}
</button>
