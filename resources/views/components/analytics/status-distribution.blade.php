@props([
    'dataset',
])

<x-analytics.donut-chart :dataset="$dataset" {{ $attributes }} />
