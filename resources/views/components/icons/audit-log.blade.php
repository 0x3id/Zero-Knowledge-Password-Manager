{{-- Vault Console icon: Audit Log — ledger page with lines + integrated clock/history glyph --}}
@props(['class' => 'h-4 w-4'])
<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
    <rect x="4" y="4" width="10" height="16" rx="2" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
    <line x1="6.2" y1="8" x2="10.2" y2="8" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="6.2" y1="11.2" x2="9.4" y2="11.2" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="6.2" y1="14.4" x2="10.6" y2="14.4" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <circle cx="17.8" cy="12" r="3" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="17.8" y1="12" x2="17.8" y2="10" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="17.8" y1="12" x2="19.1" y2="12.8" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
</svg>
