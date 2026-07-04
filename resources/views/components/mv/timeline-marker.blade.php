@props([
    'tone' => 'neutral',
    'icon' => null,
    'compact' => false,
])

@php
    $tones = [
        'neutral' => 'bg-ink-100 text-ink-600',
        'success' => 'bg-mvhab-surface text-mvhab-primary',
        'warning' => 'bg-signal-50 text-signal-800',
        'danger' => 'bg-red-50 text-red-700',
        'info' => 'bg-sky-50 text-sky-800',
        'primary' => 'bg-mvhab-primary/10 text-mvhab-primary',
    ];

    $toneClass = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->class([
    'flex shrink-0 items-center justify-center rounded-2xl ring-4 ring-white',
    $compact ? 'h-8 w-8' : 'h-10 w-10',
    $toneClass,
]) }}>
    @if ($icon)
        <x-mv-icon :name="$icon" @class([
            $compact ? 'h-4 w-4' : 'h-5 w-5',
        ]) aria-hidden="true" />
    @else
        <span class="h-2 w-2 rounded-full bg-current"></span>
    @endif
</span>
