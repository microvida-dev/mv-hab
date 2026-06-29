@props([
    'steps',
])

@php
    $max = max(array_map(fn ($step) => (float) ($step['value'] ?? 0), $steps ?: [['value' => 0]]));
    $max = $max > 0 ? $max : 1;
@endphp

<x-ui.card {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div>
        <h3 class="font-semibold text-ink-900">Funil operacional municipal</h3>
        <p class="mt-1 text-sm text-ink-500">Visualização agregada do fluxo desde a simulação até ao contrato.</p>
    </div>

    @forelse ($steps as $step)
        @php $width = max(12, (int) round(((float) ($step['value'] ?? 0) / $max) * 100)); @endphp
        <div class="rounded-2xl border border-ink-100 p-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-medium text-ink-900">{{ $step['label'] ?? 'Fase' }}</p>
                    <p class="mt-1 text-xs text-ink-500">{{ $step['description'] ?? '' }}</p>
                </div>
                <x-ui.status-badge :status="$step['status'] ?? 'neutral'" :label="(string) ($step['value'] ?? 0)" />
            </div>
            <div class="mt-3 h-2.5 rounded-full bg-ink-100" role="img" aria-label="{{ ($step['label'] ?? 'Fase').' com '.($step['value'] ?? 0).' registos' }}">
                <div class="h-2.5 rounded-full bg-mvhab-primary" style="width: {{ $width }}%"></div>
            </div>
        </div>
    @empty
        <x-analytics.analytics-empty-state title="Sem dados de funil" />
    @endforelse

    @if ($steps !== [])
        <x-analytics.analytics-table :rows="$steps" caption="Alternativa textual do funil municipal" compact />
    @endif
</x-ui.card>
