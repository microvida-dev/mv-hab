@props([
    'tone' => 'info',
])

@php
    $classes = match ($tone) {
        'warning' => 'bg-signal-50 text-signal-900',
        'danger' => 'bg-red-50 text-red-800',
        'success' => 'bg-mvhab-surface text-mvhab-primary',
        default => 'bg-ink-50 text-ink-700',
    };
@endphp

<div {{ $attributes->class([
    'rounded-2xl p-4 text-sm leading-6',
    $classes,
]) }}>
    {{ $slot }}
</div>
