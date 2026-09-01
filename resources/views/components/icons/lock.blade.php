{{-- Vault Console icon: Lock (auto-lock state) — pin-tumbler cross-section.
     Barrel + shear line base shared with the unlocked variant; here the pre-cut
     driver/key pins are engaged ACROSS the shear line = locked. Signature motif. --}}
@props(['class' => 'h-4 w-4'])
<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
    <rect x="3.5" y="6.5" width="17" height="11" rx="2.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
    <line x1="3.5" y1="12" x2="20.5" y2="12" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="7" y1="8.2" x2="7" y2="15.8" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="11" y1="8.2" x2="11" y2="14.4" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <line x1="15" y1="8.2" x2="15" y2="16.6" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <path d="M9.8 17.5 V16.9 H14.2 V17.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
</svg>
