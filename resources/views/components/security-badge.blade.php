@props([
    'type' => 'info',
    'label' => '',
])

@php
$styles = match ($type) {
    // Success / strong states → console accent (phosphor green).
    'success' => 'bg-[var(--vc-accent-soft)] text-[var(--vc-accent)] border-[color:var(--vc-accent)]/50',
    // Warning / breach states → console amber. Never red for non-destructive state.
    'warning' => 'bg-[var(--vc-warn-soft)] text-[var(--vc-warn)] border-[color:var(--vc-warn)]/50',
    // Destructive / critical failures ONLY → console danger.
    'danger' => 'bg-[color:var(--vc-danger)]/10 text-[var(--vc-danger)] border-[color:var(--vc-danger)]/50',
    // Encrypted / protected state → console accent (client-side crypto signal).
    'encrypted' => 'bg-[var(--vc-accent-soft)] text-[var(--vc-accent)] border-[color:var(--vc-accent)]/50',
    default => 'bg-[var(--vc-surface-2)] text-[var(--vc-text-dim)] border-[var(--vc-border)]',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide border {$styles}"]) }}>
    @if ($type === 'encrypted')
        <x-icon-lock class="h-3 w-3" />
    @elseif ($type === 'success')
        <x-icon-verified class="h-3 w-3" />
    @elseif ($type === 'warning')
        <x-icon-breach class="h-3 w-3" />
    @elseif ($type === 'danger')
        <x-icon-info class="h-3 w-3" />
    @else
        <x-icon-verified class="h-3 w-3" />
    @endif
    {{ $label ?: $slot }}
</span>