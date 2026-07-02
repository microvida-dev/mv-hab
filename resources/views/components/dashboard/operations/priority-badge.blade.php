@props([
    'priority' => 'medium',
])

@php
    $label = match ($priority) {
        'critical' => 'Crítico',
        'high' => 'Alto',
        'medium' => 'Médio',
        'low' => 'Baixo',
        default => ucfirst((string) $priority),
    };
@endphp

<span {{ $attributes->merge([
    'class' => 'rounded-full bg-ink-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-ink-600',
]) }}>
    {{ $label }}
</span>
