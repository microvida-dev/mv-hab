<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Candidatura formal</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $application->application_number ?? 'Rascunho' }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $application->contest->title }}</p>
            </div>
            <span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $application->status->label() }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="mv-surface p-5"><p class="text-sm text-ink-500">Candidato</p><p class="mt-2 font-semibold text-ink-900">{{ $application->adhesionRegistration->full_name }}</p></div>
                <div class="mv-surface p-5"><p class="text-sm text-ink-500">Programa</p><p class="mt-2 font-semibold text-ink-900">{{ $application->program->name }}</p></div>
                <div class="mv-surface p-5"><p class="text-sm text-ink-500">Criada</p><p class="mt-2 font-semibold text-ink-900">{{ $application->created_at->format('d/m/Y H:i') }}</p></div>
                <div class="mv-surface p-5"><p class="text-sm text-ink-500">Submetida</p><p class="mt-2 font-semibold text-ink-900">{{ $application->submitted_at?->format('d/m/Y H:i') ?? '—' }}</p></div>
            </section>

            <section class="mv-surface p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-civic-700">Elegibilidade</p>
                        <h2 class="mt-1 text-lg font-semibold text-ink-900">
                            {{ $application->latestEligibilityCheck?->result?->label() ?? 'Sem verificação formal' }}
                        </h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">
                            {{ $application->latestEligibilityCheck?->summary ?? 'Execute uma verificação formal com as regras atualmente ativas. O resultado não altera automaticamente o estado administrativo da candidatura.' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @if ($application->latestEligibilityCheck)
                            <a href="{{ route('backoffice.eligibility.checks.show', $application->latestEligibilityCheck) }}" class="mv-button-secondary">Ver detalhe técnico</a>
                        @endif
                        @can('runFormal', [\App\Models\EligibilityCheck::class, $application])
                            @if ($application->status !== \App\Enums\ApplicationStatus::Draft)
                                <form method="POST" action="{{ route('backoffice.eligibility.applications.run', $application) }}">
                                    @csrf
                                    <button class="mv-button-primary">Executar verificação formal</button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Documentos associados</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @forelse ($application->applicationDocuments as $document)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $document->documentType->name }}</p>
                                <p class="mt-1 text-xs text-ink-500">{{ $document->status_at_submission->label() }}</p>
                            </div>
                            <a href="{{ route('admin.document-reviews.show', $document->documentSubmission) }}" class="text-sm font-semibold text-civic-700">Consultar documento</a>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-ink-500">Sem documentos associados.</p>
                    @endforelse
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Declarações</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($application->declarations as $declaration)
                            <div class="rounded-md border border-ink-100 p-4 text-sm">
                                <p class="font-semibold text-ink-900">{{ $declaration->declaration_type->label() }}</p>
                                <p class="mt-1 text-ink-500">Aceite em {{ $declaration->accepted_at?->format('d/m/Y H:i') }} · versão {{ $declaration->text_version }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Histórico</h2>
                    <div class="mt-4 divide-y divide-ink-100">
                        @foreach ($application->statusHistories as $history)
                            <div class="flex justify-between gap-4 py-3 text-sm">
                                <span class="font-semibold text-ink-900">{{ $history->to_status->label() }}</span>
                                <span class="text-ink-500">{{ $history->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Snapshots preservados</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($application->snapshots as $snapshot)
                        <details class="rounded-md border border-ink-100 p-4">
                            <summary class="cursor-pointer text-sm font-semibold text-ink-900">{{ $snapshot->snapshot_type->label() }}</summary>
                            <pre class="mt-3 max-h-80 overflow-auto whitespace-pre-wrap text-xs text-ink-600">{{ json_encode($snapshot->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </details>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
