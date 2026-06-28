@props([
    'metric',
])

<x-ui.metric-card
    :href="route($metric['route'])"
    :label="$metric['label']"
    :value="$metric['value']"
    :description="$metric['description']"
    :tone="$metric['tone'] ?? 'neutral'"
/>
