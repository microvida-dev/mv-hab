@props([
    'dataset',
])

@php
    $items = collect($dataset['items'] ?? [])
        ->map(fn ($item) => [
            'label' => $item['label'] ?? 'Sem classificação',
            'value' => (float) ($item['value'] ?? 0),
        ])
        ->filter(fn ($item) => $item['value'] > 0)
        ->values();

    $rawTotal = (float) ($dataset['total'] ?? $items->sum('value'));
    $total = max($rawTotal, $items->sum('value'), 0);
    $safeTotal = max($total, 1);

    $colors = [
        '#9196F2',
        '#0597F2',
        '#27A4F2',
        '#59A8D9',
        '#64748b',
        '#7c3aed',
    ];

    $cursor = 0.0;
    $segments = [];

    foreach ($items as $index => $item) {
        $start = $cursor;
        $cursor += ($item['value'] / $safeTotal) * 100;

        $segments[] = $colors[$index % count($colors)].' '.$start.'% '.$cursor.'%';
    }

    $gradient = $segments === []
        ? '#e2e8f0 0% 100%'
        : implode(', ', $segments);
@endphp

<x-ui.card {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div>
        <h3 class="font-semibold text-ink-900">
            {{ $dataset['title'] ?? 'Distribuição' }}
        </h3>

        <p class="mt-1 text-sm text-ink-500">
            {{ $dataset['description'] ?? 'Distribuição percentual agregada.' }}
        </p>
    </div>

    @if ($items->isEmpty())
        <x-analytics.analytics-empty-state />
    @else
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            <div
                class="mx-auto flex h-36 w-36 items-center justify-center rounded-full ring-1 ring-ink-100"
                role="img"
                aria-label="{{ $dataset['title'] ?? 'Gráfico donut' }} com {{ number_format($total, 0, ',', '.') }} registos"
                style="background: conic-gradient({{ $gradient }});"
            >
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-mvhab-surface text-lg font-semibold text-ink-900 ring-1 ring-ink-100">
                    {{ number_format($total, 0, ',', '.') }}
                </div>
            </div>

            <ul class="flex-1 space-y-2 text-sm">
                @foreach ($items as $index => $item)
                    @php
                        $percentage = $safeTotal > 0
                            ? round(($item['value'] / $safeTotal) * 100, 1)
                            : 0;
                    @endphp

                    <li class="flex items-center justify-between gap-3">
                        <span class="flex min-w-0 items-center gap-2">
                            <span
                                class="h-2.5 w-2.5 shrink-0 rounded-full"
                                style="background-color: {{ $colors[$index % count($colors)] }}"
                            ></span>

                            <span class="truncate text-ink-700">
                                {{ $item['label'] }}
                            </span>
                        </span>

                        <span class="shrink-0 text-right font-semibold text-ink-900">
                            {{ number_format($item['value'], 0, ',', '.') }}
                            <span class="ml-1 text-xs font-medium text-ink-500">
                                {{ number_format($percentage, 1, ',', '.') }}%
                            </span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        <x-analytics.analytics-table
            :rows="$items->all()"
            caption="Alternativa textual do gráfico circular"
            compact
        />
    @endif
</x-ui.card>
