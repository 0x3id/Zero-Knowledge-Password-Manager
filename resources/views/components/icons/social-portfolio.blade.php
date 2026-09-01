{{-- Vault Console icon: Portfolio / dashboard-deck (line-style mark) --}}
@props(['class' => 'h-4 w-4'])
<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
    <rect x="3" y="3.5" width="18" height="13" rx="2" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
    <line x1="6" y1="9.5" x2="10" y2="9.5" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="12" y1="9.5" x2="18" y2="9.5" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="6" y1="12.6" x2="9" y2="12.6" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="6" y1="5.8" x2="10" y2="5.8" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <path d="M10.5 16.5 v1.5 H18 M4 16.5 v1.5 H10.5 M5 20.5 H19.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
</svg>
