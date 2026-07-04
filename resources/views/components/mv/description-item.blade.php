@props([
    'label',
    'value' => null,
    'hint' => null,
    'icon' => null,
    'compact' => false,
])

@php
    $content = trim($slot) ?: null;
@endphp

<div {{ $attributes->class([
    'rounded-2xl border border-ink-100 bg-ink-50/60',
    $compact ? 'p-3' : 'p-4',
]) }}>
    <dt class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-ink-500">
        @if ($icon)
            <x-mv-icon :name="$icon" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
        @endif

        <span>{{ $label }}</span>
    </dt>

    <dd @class([
        'mt-1 font-medium text-ink-900',
        'text-sm' => $compact,
        'text-base' => ! $compact,
    ])>
        @if ($content)
            {{ $slot }}
        @else
            {{ filled($value) ? $value : '—' }}
        @endif
    </dd>

    @if ($hint)
        <p class="mt-2 text-xs leading-5 text-ink-500">{{ $hint }}</p>
    @endif
</div>
