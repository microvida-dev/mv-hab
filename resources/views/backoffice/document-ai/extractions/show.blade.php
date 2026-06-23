<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-ink-900">Detalhe da extração IA</h1>
                <p class="text-sm text-ink-500">{{ $analysis->documentSubmission?->title ?? 'Documento sem título' }}</p>
            </div>
            <a href="{{ route('backoffice.document-ai.extractions.index') }}" class="mv-button-secondary">Voltar</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
            <div class="space-y-6">
                <section class="mv-surface p-6">
                    <div class="grid gap-5 md:grid-cols-4">
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Tipo documental</p>
                            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $analysis->detected_document_label ?? 'Por classificar' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Extração</p>
                            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $analysis->extraction_status?->label() ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Confiança</p>
                            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $analysis->extraction_confidence !== null ? number_format((float) $analysis->extraction_confidence * 100, 0).'%' : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Revisão</p>
                            <p class="mt-1 text-lg font-semibold {{ $analysis->extraction_requires_manual_review ? 'text-amber-700' : 'text-civic-700' }}">
                                {{ $analysis->extraction_requires_manual_review ? 'Requer revisão' : 'Sem revisão' }}
                            </p>
                        </div>
                    </div>
                </section>

                @include('backoffice.document-ai.extractions._fields-table', ['presentedFields' => $presentedFields])
            </div>

            <aside class="space-y-6">
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Documento</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-semibold text-ink-500">Tipo configurado</dt>
                            <dd class="text-ink-900">{{ $analysis->documentSubmission?->documentType?->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">Ficheiro</dt>
                            <dd class="text-ink-900">{{ $analysis->documentVersion?->original_filename ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">MIME</dt>
                            <dd class="text-ink-900">{{ $analysis->source_mime ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">Schema</dt>
                            <dd class="text-ink-900">{{ $analysis->extraction_schema_version ?? '-' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Acesso</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-semibold text-ink-500">Dados sensíveis</dt>
                            <dd class="text-ink-900">{{ $canViewSensitive ? 'Visíveis' : 'Mascarados' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">Dados de saúde</dt>
                            <dd class="text-ink-900">{{ $canViewHealth ? 'Visíveis' : 'Ocultos' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Flags</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($analysis->flags as $flag)
                            <div class="rounded-md border border-ink-100 p-3">
                                <p class="font-semibold text-ink-900">{{ $flag->code }}</p>
                                <p class="mt-1 text-sm text-ink-600">{{ $flag->message }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-ink-500">Sem flags de extração registadas.</p>
                        @endforelse
                    </div>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Execução</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($analysis->processingLogs->take(8) as $log)
                            <div class="border-l-2 border-civic-200 pl-3 text-sm">
                                <p class="font-semibold text-ink-900">{{ $log->step }}</p>
                                <p class="text-ink-500">{{ $log->message }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-ink-500">Sem logs de extração registados.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
