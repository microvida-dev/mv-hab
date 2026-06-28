<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <x-navigation.breadcrumbs />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mv-page-shell">
            <x-flash-message />

            <x-cases.case-header :summary="$workspace['summary']" />

            <x-cases.contextual-search
                :query="$workspace['contextual_search_query']"
                :results="$workspace['search_results']"
            />

            <x-cases.case-tabs :tabs="$workspace['tabs']" />

            <x-cases.case-layout :workspace="$workspace">
                <x-cases.process-progress :steps="$workspace['progress']" />

                @foreach ($workspace['tabs'] as $tab)
                    @switch($tab['key'])
                        @case('summary')
                            <x-ui.card id="case-tab-summary">
                                <x-ui.section-header title="Resumo" />
                                <x-ui.data-list class="mt-4" :items="[
                                    ['label' => 'Referência', 'value' => $workspace['summary']['reference']],
                                    ['label' => 'Estado', 'value' => $workspace['summary']['status']],
                                    ['label' => 'Criada', 'value' => $workspace['summary']['created_at']?->format('d/m/Y H:i') ?? '—'],
                                    ['label' => 'Submetida', 'value' => $workspace['summary']['submitted_at']?->format('d/m/Y H:i') ?? '—'],
                                ]" />
                            </x-ui.card>
                            <x-cases.process-checklist :items="$workspace['checklist']" />
                            @break

                        @case('timeline')
                            <x-cases.process-timeline :items="$workspace['timeline']" />
                            @break

                        @case('documents')
                            <x-ui.card id="case-tab-documents">
                                <x-ui.section-header title="Documentos" />
                                <p class="mt-2 text-sm text-ink-500">Documentos privados continuam acessíveis apenas pelas rotas protegidas de revisão documental.</p>
                            </x-ui.card>
                            @break

                        @case('eligibility')
                            <x-ui.card id="case-tab-eligibility">
                                <x-ui.section-header title="Elegibilidade" />
                                <p class="mt-2 text-sm text-ink-500">Resultado: {{ $application->latestEligibilityCheck?->result?->label() ?? 'Sem verificação formal' }}</p>
                            </x-ui.card>
                            @break

                        @case('scoring')
                            <x-ui.card id="case-tab-scoring">
                                <x-ui.section-header title="Pontuação" />
                                <p class="mt-2 text-sm text-ink-500">Pontuação operacional: {{ $application->latestApplicationScore?->total_score ?? 'Sem classificação registada' }}</p>
                            </x-ui.card>
                            @break

                        @case('lists')
                            <x-ui.card id="case-tab-lists">
                                <x-ui.section-header title="Listas" />
                                <p class="mt-2 text-sm text-ink-500">Entradas provisórias e definitivas são consultadas nos módulos de listas autorizados.</p>
                            </x-ui.card>
                            @break

                        @case('communications')
                            <x-ui.card id="case-tab-communications">
                                <x-ui.section-header title="Comunicações" />
                                <p class="mt-2 text-sm text-ink-500">Comunicações são apresentadas de forma agregada e sem conteúdo sensível desnecessário.</p>
                            </x-ui.card>
                            @break

                        @case('tasks')
                            <x-ui.card id="case-tab-tasks">
                                <x-ui.section-header title="Tarefas" />
                                <p class="mt-2 text-sm text-ink-500">Work Tasks associadas ao processo mantêm SLA, ownership e auditoria próprios.</p>
                            </x-ui.card>
                            @break

                        @case('visits')
                            <x-ui.card id="case-tab-visits">
                                <x-ui.section-header title="Visitas" />
                                <p class="mt-2 text-sm text-ink-500">Visitas associadas são tratadas pelo módulo de visitas autorizado.</p>
                            </x-ui.card>
                            @break

                        @case('rgpd')
                            <x-ui.card id="case-tab-rgpd">
                                <x-ui.section-header title="RGPD" />
                                <p class="mt-2 text-sm text-ink-500">Pedidos e direitos do titular são tratados por fluxos RGPD próprios e auditados.</p>
                            </x-ui.card>
                            @break

                        @case('audit')
                            <x-ui.card id="case-tab-audit">
                                <x-ui.section-header title="Auditoria" />
                                <p class="mt-2 text-sm text-ink-500">Auditoria é apenas consultiva neste workspace e não é editável.</p>
                            </x-ui.card>
                            @break
                    @endswitch
                @endforeach
            </x-cases.case-layout>
        </div>
    </div>
</x-app-layout>
