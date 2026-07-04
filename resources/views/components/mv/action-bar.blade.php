@props([
    'align' => 'end',
    'stack' => false,
])

@php
    $alignClass = match ($align) {
        'start' => 'justify-start',
        'center' => 'justify-center',
        'between' => 'justify-between',
        default => 'justify-end',
    };

    $layoutClass = $stack
        ? 'flex-col gap-3 sm:flex-row sm:items-center'
        : 'flex-wrap items-center gap-3';
@endphp

<div {{ $attributes->class([
    'flex',
    $layoutClass,
    $alignClass,
]) }}>
    {{ $slot }}
</div>
