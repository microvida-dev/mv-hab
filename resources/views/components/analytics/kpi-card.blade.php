@props([
    'metric',
])

<x-ui.metric-card
    :label="$metric['title'] ?? 'Indicador'"
    :value="$metric['value'] ?? '0'"
    :description="$metric['description'] ?? null"
    :href="$metric['href'] ?? null"
    :tone="$metric['tone'] ?? 'neutral'"
    {{ $attributes }}
>
    <span class="mt-3 block text-xs font-medium text-ink-400">{{ $metric['period'] ?? 'Período atual' }}</span>
    @if (! empty($metric['trend_label']))
        <span class="mt-2 inline-flex text-xs font-semibold text-mvhab-primary">{{ $metric['trend_label'] }}</span>
    @endif
</x-ui.metric-card>
