{{-- Vault Console icon: Dashboard — four-square overview grid with an active cell --}}
@props(['class' => 'h-4 w-4'])
<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
    <rect x="3.5" y="3.5" width="7" height="7" rx="1.8" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
    <rect x="13.5" y="3.5" width="7" height="7" rx="1.8" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
    <rect x="3.5" y="13.5" width="7" height="7" rx="1.8" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
    <rect x="13.5" y="13.5" width="7" height="7" rx="1.8" stroke-width="1.7" stroke="currentColor" fill="currentColor"/>
</svg>