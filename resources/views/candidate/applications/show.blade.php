<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Candidatura"
            :title="$application->application_number ?? 'Rascunho'"
            :description="$application->contest->title"
        >
            <x-slot name="actions">
                <x-mv.badge>
                    {{ $application->status->label() }}
                </x-mv.badge>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @include('candidate.applications.partials.navigation', [
                'application' => $application,
            ])

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-mv.stat-card
                    label="Programa"
                    :value="$application->program->name"
                />

                <x-mv.stat-card
                    label="Criada em"
                    :value="$application->created_at->format('d/m/Y H:i')"
                />

                <x-mv.stat-card
                    label="Submetida em"
                    :value="$application->submitted_at?->format('d/m/Y H:i') ?? 'Ainda não submetida'"
                />

                <x-mv.stat-card
                    label="Documentos associados"
                    :value="$application->applicationDocuments->count()"
                />
            </section>

            @if ($application->isEditable())
                <x-mv.section
                    eyebrow="Preparação da candidatura"
                    :title="$readiness['ready']
                        ? 'Pronta para submissão'
                        : 'Ainda existem passos por concluir'"
                >
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <x-mv.badge :tone="$readiness['ready'] ? 'success' : 'warning'">
                            {{ collect($readiness['checks'])->where('passed', true)->count() }}
                            /
                            {{ count($readiness['checks']) }}
                            concluídos
                        </x-mv.badge>
                    </div>

                    <div class="mt-5 h-2 overflow-hidden rounded-2xl bg-ink-50">
                        <div
                            class="h-full bg-mvhab-primary transition-all duration-300"
                            style="width: {{ count($readiness['checks']) > 0
                                ? round((collect($readiness['checks'])->where('passed', true)->count() / count($readiness['checks'])) * 100)
                                : 0 }}%"
                        ></div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ($readiness['checks'] as $check)
                            <x-mv.check-card
                                :label="$check['label'] ?? $check['key'] ?? 'Verificação'"
                                :detail="$check['detail'] ?? null"
                                :passed="$check['passed']"
                            />
                        @endforeach
                    </div>

                    @unless ($readiness['ready'])
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ route('candidate.registration.show') }}" class="mv-button-secondary">
                                Registo de Adesão
                            </a>

                            <a href="{{ route('candidate.household.show') }}" class="mv-button-secondary">
                                Agregado
                            </a>

                            <a href="{{ route('candidate.income-records.index') }}" class="mv-button-secondary">
                                Rendimentos
                            </a>

                            <a href="{{ route('candidate.current-housing.show') }}" class="mv-button-secondary">
                                Habitação atual
                            </a>

                            <a href="{{ route('candidate.housing-preferences.edit', $application) }}" class="mv-button-secondary">
                                Fogos
                            </a>

                            <a href="{{ route('candidate.documents.checklist') }}" class="mv-button-secondary">
                                Documentos
                            </a>
                        </div>
                    @endunless
                </x-mv.section>
            @endif

            <x-mv.section title="Resumo do processo">
                <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Candidato</p>
                        <p class="mt-2 text-sm font-semibold text-ink-900">{{ $application->adhesionRegistration->full_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Agregado</p>
                        <p class="mt-2 text-sm font-semibold text-ink-900">{{ $application->household->members->count() }} membro(s)</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Rendimento mensal</p>
                        <p class="mt-2 text-sm font-semibold text-ink-900">{{ number_format($application->household->incomeRecords->sum('monthly_amount'), 2, ',', '.') }} €</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Habitação atual</p>
                        <p class="mt-2 text-sm font-semibold text-ink-900">{{ $application->currentHousingSituation->housing_status->label() }}</p>
                    </div>
                </div>
            </x-mv.section>

            <x-mv.section
                title="Fogos"
                description="A ordem apresentada inclui todos os fogos compatíveis para este concurso."
            >
                @if ($application->housingPreferences->isNotEmpty())
                    <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($application->housingPreferences as $preference)
                            <li class="rounded-2xl border border-ink-100 p-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface font-semibold text-mvhab-primary">
                                        {{ $preference->preference_order }}
                                    </span>
                                    <div>
                                        <p class="font-semibold text-ink-900">
                                            {{ $preference->housingUnit?->public_title
                                                ?: $preference->housingUnit?->public_reference
                                                ?: 'Habitação selecionada' }}
                                        </p>
                                        <p class="mt-1 text-sm text-ink-500">
                                            {{ $preference->housingUnit?->typology ?? 'Tipologia a confirmar' }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <x-mv.alert>
                        Ainda não existe uma ordem completa de fogos registada.
                    </x-mv.alert>
                @endif

                @if ($application->isEditable())
                    <a
                        href="{{ route('candidate.housing-preferences.edit', $application) }}"
                        class="mv-button-secondary mt-5"
                    >
                        Ordenar fogos
                    </a>
                @endif
            </x-mv.section>

            @if ($application->latestEligibilityCheck)
                <x-mv.section
                    eyebrow="Última verificação de elegibilidade"
                    :title="$application->latestEligibilityCheck->result->label()"
                >
                    <p class="mt-2 text-sm leading-6 text-ink-600">{{ $application->latestEligibilityCheck->summary }}</p>
                    <p class="mt-3 text-xs leading-5 text-ink-500">Esta informação é indicativa e não substitui a decisão dos serviços municipais.</p>
                    <a href="{{ route('candidate.eligibility.show', $application->latestEligibilityCheck) }}" class="mt-4 inline-flex text-sm font-semibold text-mvhab-primary">Consultar condições verificadas</a>
                </x-mv.section>
            @endif

            @if ($application->simulationInconsistencies->isNotEmpty())
                <x-mv.section
                    eyebrow="Simulação e candidatura"
                    title="Dados a rever"
                >
                    <div class="mt-4 space-y-3">
                        @foreach ($application->simulationInconsistencies as $inconsistency)
                            <div class="border-l-2 border-mvhab-primary pl-4 text-sm">
                                <p class="font-semibold text-ink-900">{{ $inconsistency->type->label() }}</p>
                                <p class="mt-1 text-ink-600">{{ $inconsistency->message }}</p>
                            </div>
                        @endforeach
                    </div>
               </x-mv.section>
            @endif

            @if ($application->submitted_at)
                <x-mv.section
                    eyebrow="Classificação"
                    title="Fase interna do procedimento"
                >
                    <p class="mt-2 text-sm leading-6 text-ink-600">A candidatura será classificada de acordo com os critérios definidos no aviso de concurso. Os resultados provisórios serão disponibilizados em fase própria do procedimento.</p>
                </x-mv.section>
            @endif

            @if ($application->candidate_notes)
                <x-mv.section title="Notas do candidato">
                    <p class="whitespace-pre-line text-sm leading-6 text-ink-600">{{ $application->candidate_notes }}</p>
                </x-mv.section>
            @endif

            <x-mv.section title="Histórico de estado">
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach ($application->statusHistories as $history)
                        <div class="flex flex-wrap justify-between gap-3 py-4 text-sm">
                            <p class="font-semibold text-ink-900">{{ $history->to_status->label() }}</p>
                            <p class="text-ink-500">{{ $history->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </x-mv.section>

            <div class="flex flex-wrap gap-3">
                @if ($application->isEditable())
                    <a href="{{ route('candidate.applications.edit', $application) }}" class="mv-button-secondary">Editar notas</a>
                    <a href="{{ route('candidate.applications.review', $application) }}" class="mv-button-primary">Rever e submeter</a>
                @endif
                @if ($application->application_number)
                    <a href="{{ route('candidate.applications.receipt', $application) }}" class="mv-button-primary">Ver comprovativo</a>
                    <a href="{{ route('candidate.applications.print', $application) }}" class="mv-button-secondary">Imprimir</a>
                @endif
                @can('withdraw', $application)
                    <form method="POST" action="{{ route('candidate.applications.withdraw', $application) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="text" name="reason" maxlength="2000" placeholder="Motivo opcional" class="mv-input">
                        <button type="submit" class="mv-button-secondary">Desistir</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
