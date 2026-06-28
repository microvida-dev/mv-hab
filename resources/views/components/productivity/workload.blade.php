@props([
    'items' => [],
])

<section {{ $attributes->merge(['class' => 'mv-card']) }}>
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header
            title="Carga operacional"
            description="Agregados por técnico/equipa, sem dados pessoais de candidatos."
        />
    </div>

    <div class="divide-y divide-ink-100">
        @forelse ($items as $item)
            <div class="px-5 py-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-ink-900">{{ $item['name'] }}</p>
                        <p class="mt-1 text-xs text-ink-500">{{ $item['team'] ?? 'Equipa não definida' }}</p>
                    </div>
                    <x-ui.status-badge status="neutral" :label="$item['total'].' itens'" />
                </div>
                <div class="mt-3 h-2 rounded-full bg-ink-100">
                    <div class="h-2 rounded-full bg-civic-600" style="width: {{ $item['relative_load'] }}"></div>
                </div>
                <p class="mt-2 text-xs text-ink-500">
                    {{ $item['overdue'] }} em atraso · {{ $item['due_soon'] }} a vencer
                </p>
            </div>
        @empty
            <div class="p-5">
                <x-ui.empty-state
                    title="Sem carga agregada"
                    description="Não existem dados autorizados suficientes para apresentar carga operacional."
                />
            </div>
        @endforelse
    </div>
</section>
