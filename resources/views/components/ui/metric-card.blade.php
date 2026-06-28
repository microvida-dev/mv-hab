@props([
    'label',
    'value',
    'description' => null,
    'href' => null,
    'tone' => 'neutral',
])

@php
    $content = trim($slot) !== '' ? $slot : null;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'block mv-card-interactive p-4 mv-focus-ring']) }}>
        <x-ui.status-badge :status="$tone" :label="$label" />
        <span class="mt-4 block text-2xl font-semibold text-ink-900">{{ $value }}</span>
        @if ($description)
            <span class="mt-1 block text-sm leading-5 text-ink-500">{{ $description }}</span>
        @endif
        {{ $content }}
    </a>
@else
    <x-ui.card padding="p-4" {{ $attributes }}>
        <x-ui.status-badge :status="$tone" :label="$label" />
        <span class="mt-4 block text-2xl font-semibold text-ink-900">{{ $value }}</span>
        @if ($description)
            <span class="mt-1 block text-sm leading-5 text-ink-500">{{ $description }}</span>
        @endif
        {{ $content }}
    </x-ui.card>
@endif
