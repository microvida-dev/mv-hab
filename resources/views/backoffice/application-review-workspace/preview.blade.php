<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">
                Confirmação obrigatória
            </p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                Pré-visualização da operação em bloco
            </h1>
            <p class="mt-1 text-sm text-ink-500">
                Nenhuma alteração foi executada nesta etapa.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="mv-surface p-6">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                            Operação
                        </dt>
                        <dd class="mt-1 font-semibold text-ink-900">
                            {{ $preview['action_label'] }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                            Analista
                        </dt>
                        <dd class="mt-1 text-ink-700">
                            {{ $preview['assignee_name'] ?? 'Não aplicável' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                            Processos
                        </dt>
                        <dd class="mt-1 text-ink-700">
                            {{ $preview['processes']->count() }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                            Documentos
                        </dt>
                        <dd class="mt-1 text-ink-700">
                            {{ $preview['documents']->count() }}
                        </dd>
                    </div>
                </dl>

                @if ($preview['reason'])
                    <div class="mt-5 border-t border-ink-100 pt-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                            Fundamento
                        </p>
                        <p class="mt-1 text-sm text-ink-700">
                            {{ $preview['reason'] }}
                        </p>
                    </div>
                @endif

                @if ($preview['blockers'] !== [])
                    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
                        <p class="font-semibold text-red-800">
                            A operação não pode ser confirmada
                        </p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                            @foreach ($preview['blockers'] as $blocker)
                                <li>{{ $blocker }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            <section class="mv-surface overflow-hidden">
                <div class="border-b border-ink-100 px-5 py-4">
                    <h2 class="font-semibold text-ink-900">
                        Processos abrangidos
                    </h2>
                </div>
                <ul class="divide-y divide-ink-100">
                    @foreach ($preview['processes'] as $process)
                        <li class="px-5 py-3 text-sm text-ink-700">
                            <span class="font-semibold text-ink-900">
                                {{ $process->process_number }}
                            </span>
                            · {{ $process->candidate?->name ?? 'Candidato não identificado' }}
                        </li>
                    @endforeach
                </ul>
            </section>

            @if ($preview['documents']->isNotEmpty())
                <section class="mv-surface overflow-hidden">
                    <div class="border-b border-ink-100 px-5 py-4">
                        <h2 class="font-semibold text-ink-900">
                            Documentos abrangidos
                        </h2>
                    </div>
                    <ul class="divide-y divide-ink-100">
                        @foreach ($preview['documents'] as $document)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                                <span class="font-semibold text-ink-900">
                                    {{ $document->documentType?->name ?? 'Documento '.$document->id }}
                                </span>
                                <span class="text-ink-500">
                                    {{ $document->status->label() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <form
                method="POST"
                action="{{ route('backoffice.application-review-workspace.apply', $contest) }}"
                class="flex flex-wrap items-center justify-end gap-3"
            >
                @csrf
                <input type="hidden" name="action" value="{{ $preview['action']->value }}">
                <input type="hidden" name="assigned_to" value="{{ $preview['assigned_to'] }}">
                <input type="hidden" name="reason" value="{{ $preview['reason'] }}">
                <input type="hidden" name="internal_notes" value="{{ $preview['internal_notes'] }}">
                <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">

                @foreach ($preview['process_ids'] as $processId)
                    <input type="hidden" name="process_ids[]" value="{{ $processId }}">
                @endforeach

                @foreach ($preview['document_ids'] as $documentId)
                    <input type="hidden" name="document_ids[]" value="{{ $documentId }}">
                @endforeach

                <a
                    href="{{ route('backoffice.application-review-workspace.show', $contest) }}"
                    class="mv-button-secondary"
                >
                    Voltar sem alterar
                </a>

                <button
                    type="submit"
                    class="mv-button-primary"
                    @disabled($preview['blockers'] !== [])
                >
                    Confirmar operação
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
