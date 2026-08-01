<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">
                    Pedido de aperfeiçoamento
                </p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                    {{ $correctionRequest->request_number }}
                </h1>
            </div>
            <span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">
                {{ $correctionRequest->status->label() }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">Existem elementos por corrigir.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">
                    {{ $correctionRequest->subject }}
                </h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-600">
                    {{ $correctionRequest->message }}
                </p>
                <p class="mt-3 text-sm font-semibold text-ink-700">
                    Prazo:
                    {{ $correctionRequest->response_deadline_at?->format('d/m/Y H:i') ?? '—' }}
                </p>

                @if (
                    $correctionRequest->status->acceptsCandidateWork()
                    && $correctionRequest->response_deadline_at?->isPast()
                )
                    <p class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700">
                        O prazo de resposta encontra-se vencido. Contacte os
                        serviços municipais para esclarecimentos.
                    </p>
                @endif
            </section>

            @unless ($correctionRequest->isLegacy())
                <section class="mv-surface p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-ink-900">
                                Progresso da checklist
                            </h2>
                            <p class="mt-1 text-sm text-ink-600">
                                {{ $workspaceProgress['completed'] }}
                                de
                                {{ $workspaceProgress['total'] }}
                                elementos preparados.
                            </p>
                        </div>
                        <span class="text-2xl font-semibold text-civic-700">
                            {{ $workspaceProgress['percentage'] }}%
                        </span>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-ink-100">
                        <div
                            class="h-full rounded-full bg-civic-700"
                            style="width: {{ $workspaceProgress['percentage'] }}%"
                        ></div>
                    </div>

                    <p class="mt-4 rounded-md bg-civic-50 p-3 text-sm text-civic-900">
                        Guardar um elemento não o envia isoladamente ao Município.
                        A submissão formal será disponibilizada depois de toda a
                        checklist estar preparada.
                    </p>
                </section>

                @if ($correctionRequest->submissionReceipt)
                    <section class="mv-surface border border-emerald-200 p-6">
                        <h2 class="text-lg font-semibold text-emerald-900">
                            Submissão formal concluída
                        </h2>
                        <p class="mt-2 text-sm text-emerald-800">
                            Recibo
                            {{ $correctionRequest->submissionReceipt->receipt_number }}
                            emitido em
                            {{ $correctionRequest->submissionReceipt->submitted_at->format('d/m/Y H:i') }}.
                        </p>
                        <a
                            href="{{ route('candidate.correction-requests.receipt', $correctionRequest) }}"
                            class="mv-button-primary mt-4"
                        >
                            Consultar recibo
                        </a>
                    </section>
                @elseif (
                    $workspaceProgress['ready_for_submission']
                    && $correctionRequest->isResponseWindowOpen()
                )
                    <section class="mv-surface border border-civic-200 p-6">
                        <h2 class="text-lg font-semibold text-ink-900">
                            Submeter aperfeiçoamento
                        </h2>
                        <p class="mt-2 text-sm text-ink-600">
                            Todos os elementos obrigatórios estão preparados.
                            Depois da submissão deixam de poder ser alterados.
                        </p>

                        <form
                            method="POST"
                            action="{{ route('candidate.correction-requests.submit', $correctionRequest) }}"
                            class="mt-4 space-y-4"
                        >
                            @csrf
                            <label class="flex items-start gap-2 text-sm text-ink-700">
                                <input
                                    type="checkbox"
                                    name="confirm_submit"
                                    value="1"
                                    required
                                    class="mt-0.5 rounded border-ink-300"
                                >
                                Confirmo que pretendo submeter formalmente toda
                                a checklist e emitir o respetivo recibo.
                            </label>
                            <button class="mv-button-primary">
                                Submeter e emitir recibo
                            </button>
                        </form>
                    </section>
                @endif
            @endunless

            <section class="space-y-4">
                <h2 class="text-lg font-semibold text-ink-900">
                    Elementos solicitados
                </h2>

                @foreach ($correctionRequest->items as $item)
                    @php
                        $response = $item->responses
                            ->firstWhere('user_id', auth()->id())
                            ?? $item->responses->first();

                        $documentAction = in_array(
                            $item->required_action,
                            [
                                \App\Enums\CorrectionRequiredAction::UploadDocument,
                                \App\Enums\CorrectionRequiredAction::ReplaceDocument,
                            ],
                            true,
                        );
                    @endphp

                    <article class="mv-surface p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-ink-900">
                                    {{ $item->title }}
                                </h3>
                                <p class="mt-1 text-sm text-ink-600">
                                    {{ $item->description }}
                                </p>
                                <p class="mt-2 text-xs font-semibold text-ink-500">
                                    {{ $item->required_action->label() }}
                                    ·
                                    {{ $item->status->label() }}
                                </p>
                            </div>

                            @if ($response)
                                <span class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    Elemento guardado
                                </span>
                            @endif
                        </div>

                        @if ($response?->response_kind)
                            <div class="mt-4 rounded-md bg-ink-50 p-4 text-sm text-ink-700">
                                <p class="font-semibold">
                                    {{ $response->response_kind->label() }}
                                </p>

                                @if ($response->response_text)
                                    <p class="mt-1 whitespace-pre-line">
                                        {{ $response->response_text }}
                                    </p>
                                @endif

                                @if ($response->documentSubmission?->currentVersion)
                                    <p class="mt-1">
                                        Ficheiro:
                                        {{ $response->documentSubmission->currentVersion->original_filename }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if (
                            ! $correctionRequest->isLegacy()
                            && $correctionRequest->isResponseWindowOpen()
                        )
                            <form
                                method="POST"
                                action="{{ route('candidate.correction-requests.responses.store', $correctionRequest) }}"
                                enctype="multipart/form-data"
                                class="mt-5 space-y-4"
                            >
                                @csrf
                                <input
                                    type="hidden"
                                    name="correction_request_item_id"
                                    value="{{ $item->id }}"
                                >

                                @if ($documentAction)
                                    @if ($item->sourceDocumentSubmission?->currentVersion)
                                        <p class="rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                                            Documento a substituir:
                                            {{ $item->sourceDocumentSubmission->currentVersion->original_filename }}
                                        </p>
                                    @endif

                                    <div>
                                        <label class="text-sm font-semibold text-ink-800">
                                            Novo ficheiro
                                        </label>
                                        <input
                                            type="file"
                                            name="file"
                                            accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.heif"
                                            class="mt-1 block w-full rounded-md border border-ink-200 p-2 text-sm"
                                        >
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-3">
                                        <div>
                                            <label class="text-sm font-semibold text-ink-800">
                                                Mês de referência
                                            </label>
                                            <input
                                                type="month"
                                                name="reference_period"
                                                class="mt-1 block w-full rounded-md border-ink-200"
                                            >
                                        </div>
                                        <div>
                                            <label class="text-sm font-semibold text-ink-800">
                                                Data de emissão
                                            </label>
                                            <input
                                                type="date"
                                                name="issue_date"
                                                class="mt-1 block w-full rounded-md border-ink-200"
                                            >
                                        </div>
                                        <div>
                                            <label class="text-sm font-semibold text-ink-800">
                                                Data de validade
                                            </label>
                                            <input
                                                type="date"
                                                name="expiry_date"
                                                class="mt-1 block w-full rounded-md border-ink-200"
                                            >
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-sm font-semibold text-ink-800">
                                            Não dispõe do documento?
                                        </label>
                                        <textarea
                                            name="justification"
                                            rows="4"
                                            class="mt-1 block w-full rounded-md border-ink-200"
                                            placeholder="Explique de forma objetiva por que motivo não consegue apresentar o documento."
                                        >{{ $response?->response_kind === \App\Enums\CorrectionResponseKind::Justification ? $response->response_text : '' }}</textarea>
                                    </div>
                                @else
                                    <div>
                                        <label class="text-sm font-semibold text-ink-800">
                                            Resposta
                                        </label>
                                        <textarea
                                            name="response_text"
                                            rows="5"
                                            class="mt-1 block w-full rounded-md border-ink-200"
                                            required
                                        >{{ $response?->response_text }}</textarea>
                                    </div>
                                @endif

                                <button class="mv-button-primary">
                                    Guardar elemento
                                </button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </section>

            @if ($correctionRequest->isLegacy())
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">
                        Histórico de respostas
                    </h2>
                    <div class="mt-4 divide-y divide-ink-100">
                        @forelse ($correctionRequest->responses as $response)
                            <div class="py-4 text-sm">
                                <p class="font-semibold text-ink-900">
                                    {{ $response->status->label() }}
                                </p>
                                <p class="mt-1 text-ink-600">
                                    {{ $response->response_text }}
                                </p>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-ink-500">
                                Ainda não submeteu resposta.
                            </p>
                        @endforelse
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
