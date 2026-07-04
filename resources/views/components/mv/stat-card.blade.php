@props([
    'label',
    'value',
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'mv-surface p-5']) }}>
    <p class="text-sm text-ink-500">{{ $label }}</p>
    <p class="mt-3 text-2xl font-semibold text-ink-900">{{ $value }}</p>

    @if ($hint)
        <p class="mt-2 text-xs leading-5 text-ink-500">{{ $hint }}</p>
    @endif
</div>
