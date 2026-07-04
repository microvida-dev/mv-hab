@props([
    'value' => null,
])

@php
    $formatted = $value instanceof \Carbon\CarbonInterface
        ? $value->format('d/m/Y H:i')
        : $value;
@endphp

@if ($formatted)
    <time {{ $attributes->class(['text-xs font-medium text-ink-500']) }}>
        {{ $formatted }}
    </time>
@endif
