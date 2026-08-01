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

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Respostas do candidato</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @forelse ($correctionRequest->responses as $response)
                        <div class="py-4 text-sm">
                            <a href="{{ route('backoffice.correction-responses.show', $response) }}" class="font-semibold text-civic-700">{{ $response->correctionRequestItem->title }}</a>
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
