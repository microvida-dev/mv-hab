@props([
    'dataset',
])

<x-analytics.bar-chart :dataset="$dataset" {{ $attributes }} />
