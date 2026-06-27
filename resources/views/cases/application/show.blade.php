<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <x-navigation.breadcrumbs />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
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
                            <section id="case-tab-summary" class="rounded-md border border-ink-100 bg-white p-5">
                                <h2 class="text-base font-semibold text-ink-900">Resumo</h2>
                                <dl class="mt-4 grid gap-4 md:grid-cols-2">
                                    <div>
                                        <dt class="text-xs font-semibold uppercase text-ink-500">Referência</dt>
                                        <dd class="mt-1 text-sm font-semibold text-ink-900">{{ $workspace['summary']['reference'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase text-ink-500">Estado</dt>
                                        <dd class="mt-1 text-sm font-semibold text-ink-900">{{ $workspace['summary']['status'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase text-ink-500">Criada</dt>
                                        <dd class="mt-1 text-sm text-ink-700">{{ $workspace['summary']['created_at']?->format('d/m/Y H:i') ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase text-ink-500">Submetida</dt>
                                        <dd class="mt-1 text-sm text-ink-700">{{ $workspace['summary']['submitted_at']?->format('d/m/Y H:i') ?? '—' }}</dd>
                                    </div>
                                </dl>
                            </section>
                            <x-cases.process-checklist :items="$workspace['checklist']" />
                            @break

                        @case('timeline')
                            <x-cases.process-timeline :items="$workspace['timeline']" />
                            @break

                        @case('documents')
                            <section id="case-tab-documents" class="rounded-md border border-ink-100 bg-white p-5">
                                <h2 class="text-base font-semibold text-ink-900">Documentos</h2>
                                <p class="mt-2 text-sm text-ink-500">Documentos privados continuam acessíveis apenas pelas rotas protegidas de revisão documental.</p>
                            </section>
                            @break

                        @case('eligibility')
                            <section id="case-tab-eligibility" class="rounded-md border border-ink-100 bg-white p-5">
                                <h2 class="text-base font-semibold text-ink-900">Elegibilidade</h2>
                                <p class="mt-2 text-sm text-ink-500">Resultado: {{ $application->latestEligibilityCheck?->result?->label() ?? 'Sem verificação formal' }}</p>
                            </section>
                            @break

                        @case('scoring')
                            <section id="case-tab-scoring" class="rounded-md border border-ink-100 bg-white p-5">
                                <h2 class="text-base font-semibold text-ink-900">Pontuação</h2>
                                <p class="mt-2 text-sm text-ink-500">Pontuação operacional: {{ $application->latestApplicationScore?->total_score ?? 'Sem classificação registada' }}</p>
                            </section>
                            @break

                        @case('lists')
                            <section id="case-tab-lists" class="rounded-md border border-ink-100 bg-white p-5">
                                <h2 class="text-base font-semibold text-ink-900">Listas</h2>
                                <p class="mt-2 text-sm text-ink-500">Entradas provisórias e definitivas são consultadas nos módulos de listas autorizados.</p>
                            </section>
                            @break

                        @case('communications')
                            <section id="case-tab-communications" class="rounded-md border border-ink-100 bg-white p-5">
                                <h2 class="text-base font-semibold text-ink-900">Comunicações</h2>
                                <p class="mt-2 text-sm text-ink-500">Comunicações são apresentadas de forma agregada e sem conteúdo sensível desnecessário.</p>
                            </section>
                            @break

                        @case('tasks')
                            <section id="case-tab-tasks" class="rounded-md border border-ink-100 bg-white p-5">
                                <h2 class="text-base font-semibold text-ink-900">Tarefas</h2>
                                <p class="mt-2 text-sm text-ink-500">Work Tasks associadas ao processo mantêm SLA, ownership e auditoria próprios.</p>
                            </section>
                            @break

                        @case('visits')
                            <section id="case-tab-visits" class="rounded-md border border-ink-100 bg-white p-5">
                                <h2 class="text-base font-semibold text-ink-900">Visitas</h2>
                                <p class="mt-2 text-sm text-ink-500">Visitas associadas são tratadas pelo módulo de visitas autorizado.</p>
                            </section>
                            @break

                        @case('rgpd')
                            <section id="case-tab-rgpd" class="rounded-md border border-ink-100 bg-white p-5">
                                <h2 class="text-base font-semibold text-ink-900">RGPD</h2>
                                <p class="mt-2 text-sm text-ink-500">Pedidos e direitos do titular são tratados por fluxos RGPD próprios e auditados.</p>
                            </section>
                            @break

                        @case('audit')
                            <section id="case-tab-audit" class="rounded-md border border-ink-100 bg-white p-5">
                                <h2 class="text-base font-semibold text-ink-900">Auditoria</h2>
                                <p class="mt-2 text-sm text-ink-500">Auditoria é apenas consultiva neste workspace e não é editável.</p>
                            </section>
                            @break
                    @endswitch
                @endforeach
            </x-cases.case-layout>
        </div>
    </div>
</x-app-layout>
