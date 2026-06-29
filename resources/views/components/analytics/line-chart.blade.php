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
        <h3 class="font-semibold text-ink-900">{{ $dataset['title'] ?? 'Evolução' }}</h3>
        <p class="mt-1 text-sm text-ink-500">{{ $dataset['description'] ?? 'Série temporal agregada.' }}</p>
    </div>

    @if ($items === [])
        <x-analytics.analytics-empty-state />
    @else
        <div class="flex h-44 items-end gap-3 border-b border-l border-ink-100 px-2 pt-4" role="img" aria-label="{{ $dataset['title'] ?? 'Gráfico de linha' }}">
            @foreach ($items as $item)
                @php $height = max(6, (int) round(((float) ($item['value'] ?? 0) / $max) * 100)); @endphp
                <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                    <div class="w-full rounded-t-2xl bg-mvhab-primary" style="height: {{ $height }}%"></div>
                    <span class="max-w-full truncate text-xs text-ink-500">{{ $item['label'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
        <x-analytics.analytics-table :rows="$items" caption="Alternativa textual do gráfico de evolução" compact />
    @endif
</x-ui.card>
