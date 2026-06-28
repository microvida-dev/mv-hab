@props([
    'tabs' => [],
])

@php
    $normalizedTabs = collect($tabs)
        ->map(fn (array $tab): array => [
            'key' => 'case-tab-'.$tab['key'],
            'label' => $tab['label'],
            'href' => '#case-tab-'.$tab['key'],
        ])
        ->all();
@endphp

<x-ui.tabs :tabs="$normalizedTabs" aria-label="Separadores do processo" />
