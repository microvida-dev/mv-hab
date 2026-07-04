@props([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'href' => null,
])

@php
    $content = trim($slot) ?: null;
@endphp

<div {{ $attributes->merge(['class' => 'mv-surface p-5']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm text-ink-500">{{ $label }}</p>
            <p class="mt-3 text-2xl font-semibold text-ink-900">{{ $value }}</p>
        </div>

        @if ($icon)
            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                <x-mv-icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>

    @if ($hint)
        <p class="mt-2 text-xs leading-5 text-ink-500">{{ $hint }}</p>
    @endif

    @if ($content)
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif

    @if ($href)
        <a href="{{ $href }}" class="mt-4 inline-flex text-sm font-semibold text-mvhab-primary hover:text-mvhab-primary/80">
            Ver detalhe
        </a>
    @endif
</div>
