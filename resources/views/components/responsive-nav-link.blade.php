@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-s-4 border-blue-500 text-start text-base font-medium text-blue-700 bg-blue-50 dark:text-blue-300 dark:bg-blue-500/10 focus:outline-none focus:text-blue-800 focus:bg-blue-100 dark:focus:text-blue-200 dark:focus:bg-blue-500/20 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-s-4 border-transparent text-start text-base font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-50 hover:border-slate-300 focus:outline-none focus:text-slate-800 focus:bg-slate-50 focus:border-slate-300 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60 dark:hover:border-slate-600 dark:focus:text-slate-200 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
