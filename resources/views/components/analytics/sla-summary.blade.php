@props([
    'sla',
])

<x-ui.card {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div>
        <h3 class="font-semibold text-ink-900">Indicadores de SLA</h3>
        <p class="mt-1 text-sm text-ink-500">Estado agregado dos prazos de tarefas municipais.</p>
    </div>

    <x-analytics.progress-gauge
        :value="$sla['compliance_rate'] ?? 0"
        label="Taxa de conclusão face a atrasos"
        description="Indicador visual, sem recalcular regras oficiais de SLA."
    />

    <div class="grid gap-3 sm:grid-cols-2">
        @forelse (($sla['buckets'] ?? []) as $bucket)
            <div class="rounded-2xl border border-ink-100 p-3">
                <div class="flex items-center justify-between gap-3">
                    <x-ui.status-badge :status="$bucket['status'] ?? 'neutral'" :label="$bucket['label'] ?? 'SLA'" />
                    <span class="text-lg font-semibold text-ink-900">{{ $bucket['value'] ?? 0 }}</span>
                </div>
                <p class="mt-2 text-xs leading-5 text-ink-500">{{ $bucket['description'] ?? '' }}</p>
            </div>
        @empty
            <x-analytics.analytics-empty-state title="Sem dados de SLA" />
        @endforelse
    </div>
</x-ui.card>
