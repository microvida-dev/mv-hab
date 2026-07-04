@props([
    'compact' => false,
])

<div {{ $attributes->class(['relative']) }}>
    <div @class([
        'relative border-l border-ink-100',
        'space-y-4' => ! $compact,
        'space-y-3' => $compact,
    ])>
        {{ $slot }}
    </div>
</div>
