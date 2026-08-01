<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Pedido de aperfeiçoamento</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $correctionRequest->request_number }}</h1>
            </div>
            <span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $correctionRequest->status->label() }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">{{ $correctionRequest->subject }}</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-600">{{ $correctionRequest->message }}</p>
                <p class="mt-3 text-sm text-ink-500">Prazo: {{ $correctionRequest->response_deadline_at?->format('d/m/Y H:i') ?? '—' }}</p>
                @if ($correctionRequest->status === \App\Enums\CorrectionRequestStatus::Notified)
                    <form method="POST" action="{{ route('backoffice.correction-requests.issue', $correctionRequest) }}" class="mt-4 flex flex-wrap items-center gap-3">
                        @csrf
                        <label class="flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" name="confirm_issue" value="1" class="rounded border-ink-300">Ao emitir este pedido, o candidato passará a poder responder através da sua área pessoal.</label>
                        <button class="mv-button-primary">Emitir pedido</button>
                    </form>
                @endif
            </section>

            @can('extendDeadlineBackoffice', $correctionRequest)
                @if (
                    ! $correctionRequest->isLegacy()
                    && in_array(
                        $correctionRequest->status,
                        [
                            \App\Enums\CorrectionRequestStatus::Notified,
                            \App\Enums\CorrectionRequestStatus::Open,
                            \App\Enums\CorrectionRequestStatus::PartiallyCompleted,
                            \App\Enums\CorrectionRequestStatus::Expired,
                        ],
                        true,
                    )
                )
                    <section class="mv-surface p-6">
                        <h2 class="text-lg font-semibold text-ink-900">
                            Prorrogar prazo individual
                        </h2>
                        <p class="mt-2 text-sm text-ink-600">
                            O prazo original é preservado e cada prorrogação
                            fica associada ao técnico e à respetiva fundamentação.
                        </p>

                        <form
                            method="POST"
                            action="{{ route('backoffice.correction-requests.extend-deadline', $correctionRequest) }}"
                            class="mt-4 grid gap-4 md:grid-cols-2"
                        >
                            @csrf
                            <div>
                                <label class="text-sm font-semibold text-ink-800">
                                    Novo prazo
                                </label>
                                <input
                                    type="datetime-local"
                                    name="extended_deadline_at"
                                    min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                                    required
                                    class="mt-1 block w-full rounded-md border-ink-200"
                                >
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-semibold text-ink-800">
                                    Fundamentação
                                </label>
                                <textarea
                                    name="reason"
                                    rows="4"
                                    required
                                    class="mt-1 block w-full rounded-md border-ink-200"
                                >{{ old('reason') }}</textarea>
                            </div>
                            <label class="flex items-start gap-2 text-sm text-ink-700 md:col-span-2">
                                <input
                                    type="checkbox"
                                    name="confirm_extension"
                                    value="1"
                                    required
                                    class="mt-0.5 rounded border-ink-300"
                                >
                                Confirmo a autorização desta prorrogação individual.
                            </label>
                            <div class="md:col-span-2">
                                <button class="mv-button-primary">
                                    Autorizar prorrogação
                                </button>
                            </div>
                        </form>
                    </section>
                @endif
            @endcan

            @if ($correctionRequest->deadlineExtensions->isNotEmpty())
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">
                        Histórico de prorrogações
                    </h2>
                    <div class="mt-4 divide-y divide-ink-100">
                        @foreach ($correctionRequest->deadlineExtensions as $extension)
                            <div class="py-4 text-sm">
                                <p class="font-semibold text-ink-900">
                                    {{ $extension->previous_deadline_at->format('d/m/Y H:i') }}
                                    →
                                    {{ $extension->extended_deadline_at->format('d/m/Y H:i') }}
                                </p>
                                <p class="mt-1 text-ink-600">
                                    {{ $extension->reason }}
                                </p>
                                <p class="mt-1 text-xs text-ink-500">
                                    {{ $extension->authorizedBy?->name ?? 'Utilizador indisponível' }}
                                    ·
                                    {{ $extension->authorized_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($correctionRequest->submissionReceipt)
                <section class="mv-surface border border-emerald-200 p-6">
                    <h2 class="text-lg font-semibold text-emerald-900">
                        Submissão formal recebida
                    </h2>
                    <p class="mt-2 text-sm text-emerald-800">
                        Recibo:
                        {{ $correctionRequest->submissionReceipt->receipt_number }}
                        ·
                        {{ $correctionRequest->submissionReceipt->submitted_at->format('d/m/Y H:i:s') }}
                    </p>
                    <p class="mt-2 break-all font-mono text-xs text-ink-500">
                        {{ $correctionRequest->submissionReceipt->snapshot_hash }}
                    </p>
                </section>
            @endif

            <section class="mv-surface p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-ink-900">
                            Progresso agregado
                        </h2>
                        <p class="mt-1 text-sm text-ink-600">
                            {{ $requestProgress['completed'] }}
                            de
                            {{ $requestProgress['total'] }}
                            elementos preparados pelo candidato.
                        </p>
                    </div>
                    <span class="text-2xl font-semibold text-civic-700">
                        {{ $requestProgress['percentage'] }}%
                    </span>
                </div>

                <div class="mt-4 h-2 overflow-hidden rounded-full bg-ink-100">
                    <div
                        class="h-full rounded-full bg-civic-700"
                        style="width: {{ $requestProgress['percentage'] }}%"
                    ></div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-ink-100 px-3 py-1 text-ink-700">
                        {{ $requestProgress['pending'] }}
                        pendente(s)
                    </span>
                    @if ($requestProgress['formal_submitted'])
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-800">
                            Submissão formal recebida
                        </span>
                    @elseif ($requestProgress['overdue'])
                        <span class="rounded-full bg-red-50 px-3 py-1 text-red-700">
                            Prazo vencido
                        </span>
                    @elseif ($requestProgress['due_soon'])
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-800">
                            Termina em menos de 48 horas
                        </span>
                    @endif
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Itens solicitados</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach ($correctionRequest->items as $item)
                        <div class="py-4 text-sm">
                            <p class="font-semibold text-ink-900">{{ $item->title }}</p>
                            <p class="mt-1 text-ink-600">{{ $item->description }}</p>
                            <p class="mt-1 text-xs text-ink-500">{{ $item->issue_type->label() }} · {{ $item->required_action->label() }} · {{ $item->status->label() }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($revalidationWorkspace !== null)
                @php
                    $revalidationRequest = $revalidationWorkspace['request'];
                    $differential = $revalidationWorkspace['differential'];
                    $revalidationProgress = $revalidationWorkspace['progress'];
                    $revalidationBatch = $revalidationWorkspace['batch'];
                    $revalidationResponses = $differential?->request->responses->keyBy('id') ?? collect();
                    $revalidationDocuments = $revalidationWorkspace['documents'];
                @endphp

                <x-mv.section
                    eyebrow="Segunda análise"
                    title="Revalidação diferencial"
                    description="Apenas os elementos alterados ou afetados são revistos; validações anteriores não afetadas permanecem bloqueadas por carry-forward."
                >
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Resultado original</p>
                            <p class="mt-1 text-sm font-semibold text-ink-900">
                                {{ $revalidationRequest->publicationResult?->outcome?->label() ?? 'Indisponível' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Publicação original</p>
                            <p class="mt-1 text-sm font-semibold text-ink-900">
                                {{ $revalidationRequest->publicationResult?->published_at?->format('d/m/Y H:i') ?? 'Indisponível' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Recibo formal</p>
                            <p class="mt-1 text-sm font-semibold text-ink-900">
                                {{ $revalidationRequest->submissionReceipt?->receipt_number ?? 'Indisponível' }}
                            </p>
                        </div>
                    </div>

                    @if ($revalidationRequest->revalidation_started_at === null && $revalidationBatch === null)
                        @can('startRevalidationBackoffice', $revalidationRequest)
                            <form method="POST" action="{{ route('backoffice.correction-revalidations.start', $revalidationRequest) }}" class="mt-6 space-y-4">
                                @csrf
                                <x-mv.checkbox-card
                                    name="confirm_start"
                                    label="Confirmo a abertura da segunda análise com o recibo formal como fronteira temporal."
                                    align="start"
                                    required
                                />
                                <button type="submit" class="mv-button-primary">Iniciar segunda análise</button>
                            </form>
                        @endcan
                    @else
                        <p class="mt-5 text-sm text-ink-600">
                            Iniciada por
                            <span class="font-semibold text-ink-900">{{ $revalidationRequest->revalidationStartedBy?->name ?? 'Utilizador indisponível' }}</span>
                            em
                            {{ $revalidationRequest->revalidation_started_at?->format('d/m/Y H:i') ?? '—' }}.
                        </p>
                    @endif
                </x-mv.section>

                @if ($differential !== null)
                    @if ($differential->blockers !== [])
                        <x-mv.alert tone="danger">
                            <p class="font-semibold">A análise está bloqueada por alterações nas fontes:</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($differential->blockers as $blocker)
                                    <li>{{ $blocker }}</li>
                                @endforeach
                            </ul>
                        </x-mv.alert>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <x-mv.stat-card label="A rever" :value="$revalidationProgress['total']" />
                        <x-mv.stat-card label="Revistos" :value="$revalidationProgress['reviewed']" />
                        <x-mv.stat-card label="Pendentes" :value="$revalidationProgress['pending']" />
                        <x-mv.stat-card label="Progresso" :value="$revalidationProgress['percentage'].'%'" />
                    </div>

                    <x-mv.section title="Validações mantidas" description="Estes elementos foram validados anteriormente e não podem ser alterados nesta segunda análise.">
                        <div class="grid gap-3 md:grid-cols-2">
                            @forelse ($differential->carriedForwardItems() as $item)
                                <article class="rounded-2xl border border-ink-100 bg-ink-50 p-4" aria-disabled="true">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-ink-900">Documento validado #{{ $item->sourceDocumentSubmissionId }}</p>
                                            <p class="mt-1 text-sm text-ink-500">Versão {{ $item->originalDocumentVersionId ?? 'registada' }} · validação anterior preservada</p>
                                        </div>
                                        <x-mv.badge tone="success">Carry-forward</x-mv.badge>
                                    </div>
                                    @if ($revalidationDocuments->has($item->sourceDocumentSubmissionId) && \Illuminate\Support\Facades\Route::has('backoffice.cases.documents.show'))
                                        <a href="{{ route('backoffice.cases.documents.show', $revalidationDocuments->get($item->sourceDocumentSubmissionId)) }}" class="mt-3 inline-flex font-semibold text-mvhab-primary hover:underline">Consultar documento protegido</a>
                                    @endif
                                </article>
                            @empty
                                <p class="text-sm text-ink-500">Não existem validações anteriores elegíveis para carry-forward.</p>
                            @endforelse
                        </div>
                    </x-mv.section>

                    <x-mv.section title="Elementos sujeitos a decisão" description="Cada decisão fica associada à versão e ao fingerprint apresentados.">
                        <div class="space-y-5">
                            @foreach ($differential->reviewableItems() as $item)
                                @php
                                    $response = $revalidationResponses->get($item->correctionResponseId);
                                    $decisionToken = $response ? ($revalidationWorkspace['decision_tokens'][$response->id] ?? null) : null;
                                @endphp

                                <article class="rounded-2xl border border-ink-100 p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-ink-900">{{ $response?->correctionRequestItem?->title ?? 'Elemento submetido' }}</p>
                                            <p class="mt-1 text-sm text-ink-500">{{ $item->classification->label() }}</p>
                                        </div>
                                        <x-mv.badge :tone="$response?->review_result?->value === 'rejected' ? 'danger' : ($response?->review_result ? 'success' : 'warning')">
                                            {{ $response?->review_result?->label() ?? 'Aguarda decisão' }}
                                        </x-mv.badge>
                                    </div>

                                    @if ($response?->response_kind !== \App\Enums\CorrectionResponseKind::Document && filled($response?->response_text))
                                        <div class="mt-4 rounded-2xl bg-ink-50 p-4 text-sm leading-6 text-ink-700">
                                            {{ $response->response_text }}
                                        </div>
                                    @endif

                                    <div class="mt-4 flex flex-wrap gap-3 text-sm">
                                        @if ($revalidationDocuments->has($item->sourceDocumentSubmissionId) && \Illuminate\Support\Facades\Route::has('backoffice.cases.documents.show'))
                                            <a href="{{ route('backoffice.cases.documents.show', $revalidationDocuments->get($item->sourceDocumentSubmissionId)) }}" class="font-semibold text-mvhab-primary hover:underline">Consultar versão anterior</a>
                                        @endif
                                        @if ($revalidationDocuments->has($item->submittedDocumentSubmissionId) && \Illuminate\Support\Facades\Route::has('backoffice.cases.documents.show'))
                                            <a href="{{ route('backoffice.cases.documents.show', $revalidationDocuments->get($item->submittedDocumentSubmissionId)) }}" class="font-semibold text-mvhab-primary hover:underline">Consultar versão submetida</a>
                                        @endif
                                    </div>

                                    @if ($response?->reviewed_at)
                                        <p class="mt-4 text-xs text-ink-500">
                                            Revista por {{ $response->reviewedBy?->name ?? 'Utilizador indisponível' }} em {{ $response->reviewed_at->format('d/m/Y H:i') }}.
                                        </p>
                                    @endif

                                    @if ($revalidationRequest->revalidation_started_at !== null && $revalidationBatch === null && $response)
                                        @can('decideRevalidationBackoffice', $response)
                                            <form method="POST" action="{{ route('backoffice.correction-revalidations.decide', [$revalidationRequest, $response]) }}" class="mt-5 grid gap-4 md:grid-cols-3">
                                                @csrf
                                                <input type="hidden" name="source_fingerprint" value="{{ $item->sourceFingerprint }}">
                                                @if ($decisionToken)
                                                    <input type="hidden" name="expected_decision_token" value="{{ $decisionToken }}">
                                                @endif

                                                <div>
                                                    <label for="result-{{ $response->id }}" class="text-sm font-semibold text-ink-800">Decisão</label>
                                                    <select id="result-{{ $response->id }}" name="result" required class="mt-1 block w-full rounded-md border-ink-200 text-sm">
                                                        <option value="">Selecione</option>
                                                        @foreach ([
                                                            \App\Enums\CorrectionResponseReviewResult::Accepted,
                                                            \App\Enums\CorrectionResponseReviewResult::Rejected,
                                                            \App\Enums\CorrectionResponseReviewResult::NotApplicable,
                                                            \App\Enums\CorrectionResponseReviewResult::RequiresManualDecision,
                                                        ] as $resultOption)
                                                            <option value="{{ $resultOption->value }}" @selected($response->review_result === $resultOption)>{{ $resultOption->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label for="review-notes-{{ $response->id }}" class="text-sm font-semibold text-ink-800">Fundamentação da decisão</label>
                                                    <textarea id="review-notes-{{ $response->id }}" name="review_notes" rows="3" required class="mt-1 block w-full rounded-md border-ink-200 text-sm">{{ $response->review_notes }}</textarea>
                                                </div>
                                                <div class="md:col-span-3">
                                                    <button type="submit" class="mv-button-primary">Guardar decisão</button>
                                                </div>
                                            </form>
                                        @endcan
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </x-mv.section>

                    @if ($revalidationProgress['ready_to_seal'])
                        @can('previewRevalidationBackoffice', $revalidationRequest)
                            <x-mv.section title="Preparar fecho" description="A pré-visualização recalcula as fontes, o resultado agregado e os hashes antes de permitir a selagem.">
                                <form method="POST" action="{{ route('backoffice.correction-revalidations.preview', $revalidationRequest) }}" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label for="resolution_reason" class="text-sm font-semibold text-ink-800">Fundamentação do fecho</label>
                                        <textarea id="resolution_reason" name="reason" rows="4" required class="mt-1 block w-full rounded-md border-ink-200 text-sm">{{ old('reason') }}</textarea>
                                    </div>
                                    <button type="submit" class="mv-button-primary">Rever pré-visualização</button>
                                </form>
                            </x-mv.section>
                        @endcan
                    @elseif ($revalidationProgress['manual'] > 0 && $revalidationBatch === null)
                        <x-mv.alert tone="warning">
                            Existe uma decisão manual pendente. O lote não pode ser selado até essa decisão ser substituída por um resultado conclusivo.
                        </x-mv.alert>
                    @endif
                @endif

                @if ($revalidationBatch !== null)
                    <x-mv.section title="Lote da segunda análise">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-ink-900">Lote {{ $revalidationBatch->sequence_number }} · {{ $revalidationBatch->status->label() }}</p>
                                <p class="mt-1 text-sm text-ink-500">Selado em {{ $revalidationBatch->sealed_at?->format('d/m/Y H:i') ?? '—' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('backoffice.application-review-batches.show', $revalidationBatch) }}" class="mv-button-secondary">Abrir lote</a>
                                @if ($revalidationBatch->publication)
                                    <a href="{{ route('backoffice.application-review-publications.show', $revalidationBatch->publication) }}" class="mv-button-primary">Ver publicação</a>
                                @endif
                            </div>
                        </div>
                    </x-mv.section>
                @endif
            @endif

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Respostas do candidato</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @forelse ($correctionRequest->responses as $response)
                        <div class="py-4 text-sm">
                            @if ($correctionRequest->isLegacy())
                                <a href="{{ route('backoffice.correction-responses.show', $response) }}" class="font-semibold text-civic-700">{{ $response->correctionRequestItem->title }}</a>
                            @else
                                <p class="font-semibold text-ink-900">{{ $response->correctionRequestItem->title }}</p>
                            @endif
                            <p class="mt-1 text-ink-600">{{ $response->response_text }}</p>
                            <p class="mt-1 text-xs text-ink-500">{{ $response->status->label() }}</p>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-ink-500">Sem respostas submetidas.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
