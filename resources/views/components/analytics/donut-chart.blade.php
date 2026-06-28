@props([
    'dataset',
])

@php
    $items = $dataset['items'] ?? [];
    $total = max((float) ($dataset['total'] ?? 0), 1);
    $colors = ['#0f766e', '#2563eb', '#d97706', '#dc2626', '#64748b', '#7c3aed'];
    $cursor = 0.0;
    $segments = [];
    foreach ($items as $index => $item) {
        $value = (float) ($item['value'] ?? 0);
        $start = $cursor;
        $cursor += ($value / $total) * 100;
        $segments[] = $colors[$index % count($colors)].' '.$start.'% '.$cursor.'%';
    }
    $gradient = $segments === [] ? '#e2e8f0 0% 100%' : implode(', ', $segments);
@endphp

<x-ui.card {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div>
        <h3 class="font-semibold text-ink-900">{{ $dataset['title'] ?? 'Distribuição' }}</h3>
        <p class="mt-1 text-sm text-ink-500">{{ $dataset['description'] ?? 'Distribuição percentual agregada.' }}</p>
    </div>

    @if ($items === [])
        <x-analytics.analytics-empty-state />
    @else
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            <div class="mx-auto flex h-36 w-36 items-center justify-center rounded-full" role="img" aria-label="{{ $dataset['title'] ?? 'Gráfico donut' }}" style="background: conic-gradient({{ $gradient }});">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-white text-lg font-semibold text-ink-900">{{ (int) ($dataset['total'] ?? 0) }}</div>
            </div>
            <ul class="flex-1 space-y-2 text-sm">
                @foreach ($items as $index => $item)
                    <li class="flex items-center justify-between gap-3">
                        <span class="flex min-w-0 items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $colors[$index % count($colors)] }}"></span>
                            <span class="truncate text-ink-700">{{ $item['label'] ?? 'Sem classificação' }}</span>
                        </span>
                        <span class="font-semibold text-ink-900">{{ $item['value'] ?? 0 }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <x-analytics.analytics-table :rows="$items" caption="Alternativa textual do gráfico circular" compact />
    @endif
</x-ui.card>
