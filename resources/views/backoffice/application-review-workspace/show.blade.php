<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">
                    Análise progressiva em bloco
                </p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                    {{ $contest->title }}
                </h1>
                <p class="mt-1 text-sm text-ink-500">
                    As decisões ficam em rascunho técnico até ao fecho coletivo.
                    Esta mesa não envia notificações aos candidatos.
                </p>
            </div>

            <a
                href="{{ route('backoffice.application-review-workspace.index') }}"
                class="mv-button-secondary"
            >
                Alterar concurso
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if ($errors->any())
                <section class="rounded-2xl border border-red-200 bg-red-50 p-4" role="alert">
                    <p class="font-semibold text-red-800">
                        Corrija os dados da operação em bloco.
                    </p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['label' => 'Processos', 'value' => $statistics['total']],
                    ['label' => 'Sem analista', 'value' => $statistics['unassigned']],
                    ['label' => 'Em análise', 'value' => $statistics['in_progress']],
                    ['label' => 'Prontos para fecho', 'value' => $statistics['ready']],
                    ['label' => 'Documentos pendentes', 'value' => $statistics['pending_documents']],
                ] as $metric)
                    <article class="mv-surface p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                            {{ $metric['label'] }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-ink-900">
                            {{ $metric['value'] }}
                        </p>
                    </article>
                @endforeach
            </section>

            <section class="mv-surface p-5">
                <form method="GET" action="{{ route('backoffice.application-review-workspace.show', $contest) }}" class="grid gap-4 lg:grid-cols-6">
                    <div class="lg:col-span-2">
                        <label for="search" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                            Pesquisa
                        </label>
                        <input
                            id="search"
                            name="search"
                            type="search"
                            value="{{ $filters['search'] }}"
                            placeholder="Processo, candidatura, nome ou email"
                            class="mt-1 w-full rounded-2xl border-ink-200 text-sm"
                        >
                    </div>

                    <div>
                        <label for="process_status" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                            Estado do processo
                        </label>
                        <select id="process_status" name="process_status" class="mt-1 w-full rounded-2xl border-ink-200 text-sm">
                            <option value="">Todos</option>
                            @foreach ($processStatuses as $value => $label)
                                <option value="{{ $value }}" @selected($filters['process_status'] === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="review_status" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                            Estado da análise
                        </label>
                        <select id="review_status" name="review_status" class="mt-1 w-full rounded-2xl border-ink-200 text-sm">
                            <option value="">Todos</option>
                            @foreach ($reviewStatuses as $value => $label)
                                <option value="{{ $value }}" @selected($filters['review_status'] === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="assigned_to" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                            Analista
                        </label>
                        <select id="assigned_to" name="assigned_to" class="mt-1 w-full rounded-2xl border-ink-200 text-sm">
                            <option value="">Todos</option>
                            @foreach ($analysts as $analyst)
                                <option value="{{ $analyst->id }}" @selected($filters['assigned_to'] === $analyst->id)>
                                    {{ $analyst->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="readiness" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                            Prontidão
                        </label>
                        <select id="readiness" name="readiness" class="mt-1 w-full rounded-2xl border-ink-200 text-sm">
                            <option value="">Todas</option>
                            <option value="ready" @selected($filters['readiness'] === 'ready')>Prontas para fecho</option>
                            <option value="not_ready" @selected($filters['readiness'] === 'not_ready')>Ainda não prontas</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 lg:col-span-6">
                        <button type="submit" class="mv-button-primary">
                            Aplicar filtros
                        </button>
                        <a
                            href="{{ route('backoffice.application-review-workspace.show', $contest) }}"
                            class="mv-button-secondary"
                        >
                            Limpar
                        </a>
                    </div>
                </form>
            </section>

            <form
                id="bulk-review-form"
                method="POST"
                action="{{ route('backoffice.application-review-workspace.preview', $contest) }}"
                class="space-y-5"
            >
                @csrf

                <section class="mv-surface p-5">
                    <div class="grid gap-4 lg:grid-cols-4">
                        <div>
                            <label for="action" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Operação em bloco
                            </label>
                            <select id="action" name="action" required class="mt-1 w-full rounded-2xl border-ink-200 text-sm">
                                <option value="">Selecionar operação</option>
                                @foreach ($bulkActions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('action') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="bulk_assigned_to" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Analista a atribuir
                            </label>
                            <select id="bulk_assigned_to" name="assigned_to" class="mt-1 w-full rounded-2xl border-ink-200 text-sm">
                                <option value="">Não aplicável</option>
                                @foreach ($analysts as $analyst)
                                    <option value="{{ $analyst->id }}" @selected((int) old('assigned_to') === $analyst->id)>
                                        {{ $analyst->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="reason" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Fundamento comum
                            </label>
                            <textarea
                                id="reason"
                                name="reason"
                                rows="2"
                                maxlength="2000"
                                class="mt-1 w-full rounded-2xl border-ink-200 text-sm"
                            >{{ old('reason') }}</textarea>
                        </div>

                        <div>
                            <label for="internal_notes" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Nota interna
                            </label>
                            <textarea
                                id="internal_notes"
                                name="internal_notes"
                                rows="2"
                                maxlength="5000"
                                class="mt-1 w-full rounded-2xl border-ink-200 text-sm"
                            >{{ old('internal_notes') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 pt-4">
                        <p class="text-sm text-ink-500">
                            Selecione os processos e, nas operações documentais, os documentos abrangidos.
                        </p>
                        <button type="submit" class="mv-button-primary">
                            Pré-visualizar operação
                        </button>
                    </div>
                </section>

                <section class="space-y-4">
                    @forelse ($processes as $process)
                        @php
                            $application = $process->application;
                            $review = $process->latestDocumentalReview;
                            $reviewStatus = $review?->status?->label() ?? 'Não iniciada';
                            $ready = $review?->isReadyForClosure() ?? false;
                        @endphp

                        <article class="mv-surface overflow-hidden">
                            <div class="grid gap-4 p-5 lg:grid-cols-[auto_minmax(0,1fr)_auto] lg:items-start">
                                <div class="pt-1">
                                    <input
                                        type="checkbox"
                                        name="process_ids[]"
                                        value="{{ $process->id }}"
                                        @checked(in_array($process->id, array_map('intval', old('process_ids', [])), true))
                                        class="rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary"
                                        aria-label="Selecionar processo {{ $process->process_number }}"
                                    >
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-semibold text-ink-900">
                                            {{ $process->process_number }}
                                        </h2>
                                        <span class="rounded-full bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">
                                            {{ $process->status->label() }}
                                        </span>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $ready ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700' }}">
                                            {{ $reviewStatus }}
                                        </span>
                                    </div>

                                    <p class="mt-1 text-sm text-ink-600">
                                        {{ $process->candidate?->name ?? 'Candidato não identificado' }}
                                        @if ($application?->application_number)
                                            · {{ $application->application_number }}
                                        @endif
                                    </p>

                                    <dl class="mt-4 grid gap-3 text-xs sm:grid-cols-3 xl:grid-cols-6">
                                        <div>
                                            <dt class="font-semibold uppercase tracking-wide text-ink-400">Analista</dt>
                                            <dd class="mt-1 text-ink-700">{{ $process->assignedTo?->name ?? 'Não atribuído' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-semibold uppercase tracking-wide text-ink-400">Documentos</dt>
                                            <dd class="mt-1 text-ink-700">{{ $application?->documents_total ?? 0 }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-semibold uppercase tracking-wide text-ink-400">Submetidos</dt>
                                            <dd class="mt-1 text-ink-700">{{ $application?->documents_submitted ?? 0 }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-semibold uppercase tracking-wide text-ink-400">Em análise</dt>
                                            <dd class="mt-1 text-ink-700">{{ $application?->documents_under_review ?? 0 }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-semibold uppercase tracking-wide text-ink-400">Validados</dt>
                                            <dd class="mt-1 text-ink-700">{{ $application?->documents_validated ?? 0 }}</dd>
                                        </div>
                                        <div>
                                            <dt class="font-semibold uppercase tracking-wide text-ink-400">Rejeitados/expirados</dt>
                                            <dd class="mt-1 text-ink-700">
                                                {{ ($application?->documents_rejected ?? 0) + ($application?->documents_expired ?? 0) }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <a
                                    href="{{ route('backoffice.administrative-processes.show', $process) }}"
                                    class="mv-button-secondary"
                                >
                                    Abrir processo
                                </a>
                            </div>

                            @if ($application?->documentSubmissions?->isNotEmpty())
                                <details class="border-t border-ink-100">
                                    <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-civic-700">
                                        Selecionar documentos desta candidatura
                                    </summary>
                                    <div class="grid gap-2 border-t border-ink-100 px-5 py-4 md:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($application->documentSubmissions as $document)
                                            <label class="flex items-start gap-3 rounded-xl border border-ink-100 p-3">
                                                <input
                                                    type="checkbox"
                                                    name="document_ids[]"
                                                    value="{{ $document->id }}"
                                                    @checked(in_array($document->id, array_map('intval', old('document_ids', [])), true))
                                                    class="mt-1 rounded border-ink-300 text-mvhab-primary focus:ring-mvhab-primary"
                                                >
                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-semibold text-ink-900">
                                                        {{ $document->documentType?->name ?? 'Documento' }}
                                                    </span>
                                                    <span class="mt-0.5 block text-xs text-ink-500">
                                                        {{ $document->status->label() }}
                                                    </span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        </article>
                    @empty
                        <section class="mv-surface px-6 py-12 text-center">
                            <p class="font-semibold text-ink-900">
                                Sem processos para apresentar
                            </p>
                            <p class="mt-1 text-sm text-ink-500">
                                Ajuste os filtros ou confirme a receção administrativa das candidaturas.
                            </p>
                        </section>
                    @endforelse
                </section>
            </form>

            <div>
                {{ $processes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
