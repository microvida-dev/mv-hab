@props([
    'items' => [],
    'columns' => 1,
    'compact' => false,
])

@php
    $gridClass = match ((int) $columns) {
        2 => 'sm:grid-cols-2',
        3 => 'sm:grid-cols-2 lg:grid-cols-3',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'grid-cols-1',
    };

    $gapClass = $compact ? 'gap-3' : 'gap-4';
@endphp

<dl {{ $attributes->class([
    'grid',
    $gridClass,
    $gapClass,
]) }}>
    @foreach ($items as $label => $value)
        <x-mv.description-item :label="$label" :value="$value" :compact="$compact" />
    @endforeach

    {{ $slot }}
</dl>
