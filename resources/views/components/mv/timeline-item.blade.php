@props([
    'title',
    'description' => null,
    'date' => null,
    'icon' => null,
    'tone' => 'neutral',
    'compact' => false,
])

<div {{ $attributes->class([
    'relative pl-6',
]) }}>
    <x-mv.timeline-marker
        :tone="$tone"
        :icon="$icon"
        :compact="$compact"
        class="absolute -left-5 top-0"
    />

    <div @class([
        'rounded-2xl border border-ink-100 bg-white',
        $compact ? 'p-3' : 'p-4',
    ])>
        <div class="flex flex-wrap items-start justify-between gap-2">
            <p class="font-semibold text-ink-900">
                {{ $title }}
            </p>

            <x-mv.timeline-date :value="$date" />
        </div>

        @if ($description)
            <p class="mt-1 text-sm leading-6 text-ink-500">
                {{ $description }}
            </p>
        @endif

        @if (trim($slot))
            <div class="mt-3 text-sm leading-6 text-ink-600">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
