@props([
    'tone' => 'info',
    'title' => null,
    'icon' => null,
    'bordered' => false,
])

@php
    $tones = [
        'info' => [
            'classes' => 'bg-ink-50 text-ink-700 border-ink-100',
            'icon' => 'info',
        ],
        'success' => [
            'classes' => 'bg-mvhab-surface text-mvhab-primary border-mvhab-primary/10',
            'icon' => 'check',
        ],
        'warning' => [
            'classes' => 'bg-signal-50 text-signal-900 border-signal-100',
            'icon' => 'alert',
        ],
        'danger' => [
            'classes' => 'bg-red-50 text-red-800 border-red-100',
            'icon' => 'alert',
        ],
    ];

    $variant = $tones[$tone] ?? $tones['info'];
    $iconName = $icon ?: $variant['icon'];

    $classes = collect([
        'rounded-2xl p-4 text-sm leading-6',
        $bordered ? 'border' : 'border border-transparent',
        $variant['classes'],
    ])->implode(' ');
@endphp

<div {{ $attributes->class([$classes]) }}>
    <div class="flex gap-3">
        @if ($iconName)
            <x-mv-icon :name="$iconName" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
        @endif

        <div class="min-w-0 flex-1">
            @if ($title)
                <p class="font-semibold text-current">{{ $title }}</p>
            @endif

            <div @class(['mt-1' => $title])>
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
