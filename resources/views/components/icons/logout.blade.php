{{-- Vault Console icon: Log out — a broken/opened padlock-barred bolt = exit --}}
@props(['class' => 'h-4 w-4'])
<svg {{ $attributes->merge(['class' => $class]) }} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
    <path d="M14 4 H18 a2 2 0 0 1 2 2 v12 a2 2 0 0 1 -2 2 H14" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
    <path d="M10 8 l-4 4 4 4" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor"/>
    <path d="M6 12 H17.5" stroke-width="1.7" stroke-linecap="round" stroke="currentColor"/>
</svg>
