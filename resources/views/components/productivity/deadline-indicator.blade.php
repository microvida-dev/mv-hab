@props([
    'deadline' => null,
])

@php
    $deadline ??= [
        'state' => 'neutral',
        'label' => 'Sem prazo',
        'description' => 'Sem prazo operacional definido.',
    ];
@endphp

<span class="inline-flex items-center gap-2" title="{{ $deadline['description'] ?? '' }}">
    <x-ui.status-badge :status="$deadline['state'] ?? 'neutral'" :label="$deadline['label'] ?? 'Sem prazo'" />
</span>
