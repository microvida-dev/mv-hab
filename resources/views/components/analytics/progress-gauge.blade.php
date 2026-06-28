@props([
    'value' => 0,
    'label' => 'Progresso',
    'description' => null,
    'tone' => 'civic',
])

@php
    $safeValue = max(0, min(100, (int) $value));
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    <div class="flex items-center justify-between gap-3">
        <span class="text-sm font-medium text-ink-700">{{ $label }}</span>
        <span class="text-sm font-semibold text-ink-900">{{ $safeValue }}%</span>
    </div>
    <div class="h-2.5 rounded-full bg-ink-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $safeValue }}" aria-label="{{ $label }}">
        <div class="h-2.5 rounded-full bg-civic-600" style="width: {{ $safeValue }}%"></div>
    </div>
    @if ($description)
        <p class="text-xs text-ink-500">{{ $description }}</p>
    @endif
</div>
