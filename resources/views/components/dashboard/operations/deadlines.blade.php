@props([
    'items' => [],
])

<x-dashboard.operations.expandable-panel
    id="deadlines"
    eyebrow="Prazos"
    title="Alertas e prazos"
    description="Prazos processuais e alertas que requerem acompanhamento."
    icon="calendar"
    :default-open="false"
    :summary="[
        count($items).' alerta(s)',
        count($items) > 0 ? 'Requer acompanhamento' : 'Sem alertas ativos',
    ]"
>
    <div class="divide-y divide-ink-100">
        @forelse ($items as $alert)
            <x-dashboard.deadline-alert :alert="$alert" />
        @empty
            <div class="p-5">
                <x-dashboard.empty-state
                    title="Sem alertas ativos"
                    description="Não existem prazos ou alertas autorizados para apresentar."
                />
            </div>
        @endforelse
    </div>
</x-dashboard.operations.expandable-panel>
