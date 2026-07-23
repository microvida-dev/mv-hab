@props([
    'tone' => 'info',
])

@php
    $classes = match ($tone) {
        'warning' => 'bg-signal-50 text-signal-900',
        'danger' => 'bg-red-50 text-red-800',
        'success' => 'bg-mvhab-surface text-mvhab-primary',
        default => 'bg-ink-50 text-ink-700',
    };
    $alertRole = $attributes->get('role')
        ?? (in_array($tone, ['warning', 'danger'], true) ? 'alert' : 'status');
    $ariaLive = $attributes->get('aria-live')
        ?? ($alertRole === 'alert' ? 'assertive' : 'polite');
@endphp

<div
    role="{{ $alertRole }}"
    aria-live="{{ $ariaLive }}"
    {{ $attributes->except(['role', 'aria-live'])->class([
        'rounded-2xl p-4 text-sm leading-6',
        $classes,
    ]) }}
>
    {{ $slot }}
</div>
