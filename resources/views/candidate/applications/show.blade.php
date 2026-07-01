<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Candidatura</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $application->application_number ?? 'Rascunho' }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $application->contest->title }}</p>
            </div>
            <span class="rounded-2xl bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $application->status->label() }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Programa</p>
                    <p class="mt-2 font-semibold text-ink-900">{{ $application->program->name }}</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Criada em</p>
                    <p class="mt-2 font-semibold text-ink-900">{{ $application->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Submetida em</p>
                    <p class="mt-2 font-semibold text-ink-900">{{ $application->submitted_at?->format('d/m/Y H:i') ?? 'Ainda não submetida' }}</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Documentos associados</p>
                    <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $application->applicationDocuments->count() }}</p>
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Resumo do processo</h2>
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
            </section>

            @if ($application->latestEligibilityCheck)
                <section class="mv-surface p-6">
                    <p class="text-sm font-semibold text-mvhab-primary">Última verificação de elegibilidade</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-900">{{ $application->latestEligibilityCheck->result->label() }}</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">{{ $application->latestEligibilityCheck->summary }}</p>
                    <p class="mt-3 text-xs leading-5 text-ink-500">Esta informação é indicativa e não substitui a decisão dos serviços municipais.</p>
                    <a href="{{ route('candidate.eligibility.show', $application->latestEligibilityCheck) }}" class="mt-4 inline-flex text-sm font-semibold text-mvhab-primary">Consultar condições verificadas</a>
                </section>
            @endif

            @if ($application->simulationInconsistencies->isNotEmpty())
                <section class="mv-surface p-6">
                    <p class="text-sm font-semibold text-mvhab-primary">Simulação e candidatura</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-900">Dados a rever</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($application->simulationInconsistencies as $inconsistency)
                            <div class="border-l-2 border-mvhab-primary pl-4 text-sm">
                                <p class="font-semibold text-ink-900">{{ $inconsistency->type->label() }}</p>
                                <p class="mt-1 text-ink-600">{{ $inconsistency->message }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($application->submitted_at)
                <section class="mv-surface p-6">
                    <p class="text-sm font-semibold text-mvhab-primary">Classificação</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-900">Fase interna do procedimento</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">A candidatura será classificada de acordo com os critérios definidos no aviso de concurso. Os resultados provisórios serão disponibilizados em fase própria do procedimento.</p>
                </section>
            @endif

            @if ($application->candidate_notes)
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Notas do candidato</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-600">{{ $application->candidate_notes }}</p>
                </section>
            @endif

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Histórico de estado</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach ($application->statusHistories as $history)
                        <div class="flex flex-wrap justify-between gap-3 py-4 text-sm">
                            <p class="font-semibold text-ink-900">{{ $history->to_status->label() }}</p>
                            <p class="text-ink-500">{{ $history->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

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
