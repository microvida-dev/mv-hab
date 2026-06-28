@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'secondary',
    'disabled' => false,
])

@php
    $classes = [
        'primary' => 'mv-button-primary',
        'secondary' => 'mv-button-secondary',
        'danger' => 'mv-button-danger',
    ][$variant] ?? 'mv-button-secondary';
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
