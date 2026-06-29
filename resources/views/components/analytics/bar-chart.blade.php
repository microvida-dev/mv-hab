@props([
    'dataset',
])

@php
    $items = $dataset['items'] ?? [];
    $max = max(array_map(fn ($item) => (float) ($item['value'] ?? 0), $items ?: [['value' => 0]]));
    $max = $max > 0 ? $max : 1;
@endphp

<x-ui.card {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div>
        <h3 class="font-semibold text-ink-900">{{ $dataset['title'] ?? 'Distribuição' }}</h3>
        <p class="mt-1 text-sm text-ink-500">{{ $dataset['description'] ?? 'Distribuição agregada.' }}</p>
    </div>

    @forelse ($items as $item)
        @php $width = max(4, (int) round(((float) ($item['value'] ?? 0) / $max) * 100)); @endphp
        <div>
            <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                <span class="truncate font-medium text-ink-700">{{ $item['label'] ?? 'Sem classificação' }}</span>
                <span class="font-semibold text-ink-900">{{ $item['value'] ?? 0 }}</span>
            </div>
            <div class="h-2.5 rounded-full bg-ink-100" role="img" aria-label="{{ ($item['label'] ?? 'Indicador').' com '.($item['value'] ?? 0).' ocorrências' }}">
                <div class="h-2.5 rounded-full bg-mvhab-primary" style="width: {{ $width }}%"></div>
            </div>
        </div>
    @empty
        <x-analytics.analytics-empty-state />
    @endforelse

    @if ($items !== [])
        <x-analytics.analytics-table :rows="$items" caption="Alternativa textual do gráfico de barras" compact />
    @endif
</x-ui.card>
