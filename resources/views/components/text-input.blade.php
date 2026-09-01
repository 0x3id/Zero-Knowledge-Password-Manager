@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-white/70 bg-white/40 text-slate-900 placeholder-slate-400 backdrop-blur-glass focus:border-blue-500 focus:ring-blue-500/30 rounded-xl shadow-glass-sm transition duration-200 ease-in-out dark:border-white/10 dark:bg-white/[0.045] dark:text-slate-100 dark:placeholder-slate-500 dark:hover:border-white/20 dark:focus:border-blue-500 dark:focus:ring-blue-500/40']) }}>
