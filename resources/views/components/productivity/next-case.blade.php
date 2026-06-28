@props([
    'nextCase' => null,
])

<section {{ $attributes->merge(['class' => 'mv-card']) }}>
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header
            title="Próximo processo sugerido"
            description="Sugestão operacional baseada em prioridade, SLA e prazo. Não altera dados."
        />
    </div>

    <div class="p-5">
        @if ($nextCase)
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-ink-900">{{ $nextCase['title'] }}</p>
                    <p class="mt-1 text-sm text-ink-500">{{ $nextCase['reason'] ?? 'Sugerido por prioridade operacional.' }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <x-ui.status-badge :status="$nextCase['status']" :label="$nextCase['status_label']" />
                        <x-ui.status-badge :status="$nextCase['priority']" :label="$nextCase['priority_label']" />
                        <x-productivity.deadline-indicator :deadline="$nextCase['deadline'] ?? null" />
                    </div>
                </div>
                <x-ui.action-button :href="$nextCase['url']" variant="primary">
                    <span>Abrir processo</span>
                    <x-ui-icon name="arrow" class="h-4 w-4" />
                </x-ui.action-button>
            </div>
        @else
            <x-ui.empty-state
                title="Sem próximo processo"
                description="Não existem processos autorizados para sugerir neste momento."
            />
        @endif
    </div>
</section>
