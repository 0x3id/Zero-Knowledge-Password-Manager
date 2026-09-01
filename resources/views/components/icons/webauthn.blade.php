{{-- Vault Console icon: Two-factor / WebAuthn — concentric ridge arcs (clean geometric whorl,
     not a literal fingerprint scan). Two Xs = second factor. --}}
@props(['class' => 'h-4 w-4'])
<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
    <path d="M5.05 7.65 A8.2 8.2 0 1 1 18.95 7.65" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <path d="M6.57 8.61 A6.4 6.4 0 1 1 17.43 8.61" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
    <path d="M8.10 9.56 A4.6 4.6 0 1 1 15.90 9.56" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
</svg>
