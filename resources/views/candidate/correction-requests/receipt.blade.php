<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">
                Recibo de submissão
            </p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                {{ $receipt->receipt_number }}
            </h1>
        </div>
    </x-slot>

    <div class="py-8 print:py-0">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8 print:max-w-none print:px-0">
            <section class="mv-surface p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-ink-900">
                            Aperfeiçoamento submetido
                        </h2>
                        <p class="mt-1 text-sm text-ink-600">
                            Pedido {{ $correctionRequest->request_number }}
                        </p>
                    </div>
                    <button
                        type="button"
                        onclick="window.print()"
                        class="mv-button-secondary print:hidden"
                    >
                        Imprimir
                    </button>
                </div>

                <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold text-ink-700">
                            Submetido em
                        </dt>
                        <dd class="mt-1 text-ink-600">
                            {{ $receipt->submitted_at->format('d/m/Y H:i:s') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink-700">
                            Candidatura
                        </dt>
                        <dd class="mt-1 text-ink-600">
                            {{ data_get($snapshot, 'application.number') ?? '—' }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-semibold text-ink-700">
                            Hash de integridade
                        </dt>
                        <dd class="mt-1 break-all font-mono text-xs text-ink-600">
                            {{ $receipt->snapshot_hash }}
                        </dd>
                    </div>
                </dl>

                <p class="mt-6 rounded-md bg-civic-50 p-3 text-sm text-civic-900">
                    Este recibo corresponde à fotografia imutável da checklist
                    e das versões documentais existentes no momento da submissão.
                </p>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">
                    Elementos submetidos
                </h2>

                <div class="mt-4 divide-y divide-ink-100">
                    @foreach (data_get($snapshot, 'items', []) as $item)
                        <article class="py-4 text-sm">
                            <p class="font-semibold text-ink-900">
                                {{ $item['title'] ?? 'Elemento' }}
                            </p>
                            <p class="mt-1 text-ink-600">
                                Tipo de resposta:
                                {{ data_get($item, 'response.kind') ?? 'Dispensado' }}
                            </p>

                            @if (data_get($item, 'response.text'))
                                <p class="mt-2 whitespace-pre-line text-ink-600">
                                    {{ data_get($item, 'response.text') }}
                                </p>
                            @endif

                            @if (data_get($item, 'response.document_version'))
                                <dl class="mt-3 grid gap-2 rounded-md bg-ink-50 p-3 text-xs sm:grid-cols-2">
                                    <div>
                                        <dt class="font-semibold text-ink-700">
                                            Ficheiro
                                        </dt>
                                        <dd class="mt-1 text-ink-600">
                                            {{ data_get($item, 'response.document_version.original_filename') }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold text-ink-700">
                                            Versão
                                        </dt>
                                        <dd class="mt-1 text-ink-600">
                                            {{ data_get($item, 'response.document_version.version_number') }}
                                        </dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="font-semibold text-ink-700">
                                            Checksum
                                        </dt>
                                        <dd class="mt-1 break-all font-mono text-ink-600">
                                            {{ data_get($item, 'response.document_version.checksum') }}
                                        </dd>
                                    </div>
                                </dl>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            <a
                href="{{ route('candidate.correction-requests.show', $correctionRequest) }}"
                class="mv-button-secondary print:hidden"
            >
                Voltar ao pedido
            </a>
        </div>
    </div>
</x-app-layout>
