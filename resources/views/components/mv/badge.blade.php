@props([
    'tone' => 'neutral',
    'size' => 'sm',
    'icon' => null,
    'outline' => false,
    'pill' => true,
])

@php
    $tones = [
        'neutral' => $outline
            ? 'border-ink-200 bg-white text-ink-700'
            : 'border-ink-100 bg-ink-100 text-ink-700',

        'success' => $outline
            ? 'border-mvhab-primary/30 bg-white text-mvhab-primary'
            : 'border-mvhab-primary/10 bg-mvhab-surface text-mvhab-primary',

        'warning' => $outline
            ? 'border-signal-200 bg-white text-signal-800'
            : 'border-signal-100 bg-signal-50 text-signal-800',

        'danger' => $outline
            ? 'border-red-200 bg-white text-red-800'
            : 'border-red-100 bg-red-50 text-red-800',

        'info' => $outline
            ? 'border-sky-200 bg-white text-sky-800'
            : 'border-sky-100 bg-sky-50 text-sky-800',

        'primary' => $outline
            ? 'border-mvhab-primary/30 bg-white text-mvhab-primary'
            : 'border-mvhab-primary/10 bg-mvhab-primary/10 text-mvhab-primary',
    ];

    $sizes = [
        'xs' => 'px-2 py-0.5 text-[0.68rem]',
        'sm' => 'px-2.5 py-1 text-xs',
        'md' => 'px-3 py-1.5 text-sm',
    ];

    $shape = $pill ? 'rounded-2xl' : 'rounded-xl';

    $classes = collect([
        'inline-flex items-center gap-1.5 border font-semibold leading-none',
        $shape,
        $tones[$tone] ?? $tones['neutral'],
        $sizes[$size] ?? $sizes['sm'],
    ])->implode(' ');
@endphp

<span {{ $attributes->class([$classes]) }}>
    @if ($icon)
        <x-mv-icon :name="$icon" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
    @endif

    {{ $slot }}
</span>
