@props([
    'tone' => 'neutral',
])

@php
    $classes = match ($tone) {
        'success' => 'bg-mvhab-surface text-mvhab-primary',
        'warning' => 'bg-signal-50 text-signal-800',
        'danger' => 'bg-red-50 text-red-800',
        default => 'bg-ink-100 text-ink-700',
    };
@endphp

<span {{ $attributes->class([
    'inline-flex items-center rounded-2xl px-2.5 py-1 text-xs font-semibold',
    $classes,
]) }}>
    {{ $slot }}
</span>
