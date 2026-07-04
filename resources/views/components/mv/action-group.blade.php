@props([
    'align' => 'start',
    'stack' => false,
    'compact' => false,
])

@php
    $alignClass = match ($align) {
        'center' => 'justify-center',
        'end' => 'justify-end',
        default => 'justify-start',
    };

    $gapClass = $compact ? 'gap-2' : 'gap-3';

    $layoutClass = $stack
        ? 'flex-col sm:flex-row sm:items-center'
        : 'flex-wrap items-center';
@endphp

<div {{ $attributes->class([
    'flex',
    $layoutClass,
    $alignClass,
    $gapClass,
]) }}>
    {{ $slot }}
</div>
