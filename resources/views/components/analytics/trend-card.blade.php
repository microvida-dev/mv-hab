@props([
    'dataset',
])

<x-analytics.line-chart :dataset="$dataset" {{ $attributes }} />
