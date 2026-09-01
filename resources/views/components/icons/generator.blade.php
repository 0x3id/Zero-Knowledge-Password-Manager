{{-- Vault Console icon: Password Generator — graduated randomizer dial (gauge ticks + needle) --}}
@props(['class' => 'h-4 w-4'])
<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
    <circle cx="12" cy="12" r="7.6" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
    <line x1="12" y1="5.4" x2="12" y2="7" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="16.9" y1="11.4" x2="18.5" y2="12" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="12" y1="17" x2="12" y2="18.6" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="5.5" y1="11.4" x2="7.1" y2="12" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <circle cx="12" cy="12" r="0.9" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="12" y1="12" x2="14.9" y2="8.5" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
</svg>
