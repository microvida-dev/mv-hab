<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-ink-900">Detalhe da classificação IA</h1>
                <p class="text-sm text-ink-500">{{ $analysis->documentSubmission?->title ?? 'Documento sem título' }}</p>
            </div>
            <a href="{{ route('backoffice.document-ai.classifications.index') }}" class="mv-button-secondary">Voltar</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
            <div class="space-y-6">
                <section class="mv-surface p-6">
                    <div class="grid gap-5 md:grid-cols-4">
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Classificação IA</p>
                            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $analysis->detected_document_label ?? 'Por classificar' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Confiança</p>
                            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $analysis->classification_confidence !== null ? number_format((float) $analysis->classification_confidence * 100, 0).'%' : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Estado</p>
                            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $analysis->classification_status?->label() ?? $analysis->status->label() }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">OCR</p>
                            <p class="mt-1 text-lg font-semibold {{ $analysis->ocr_available ? 'text-mvhab-primary' : 'text-amber-700' }}">{{ $analysis->ocr_available ? 'Disponível' : 'Indisponível' }}</p>
                        </div>
                    </div>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Sinais de classificação</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @forelse (($analysis->classification_signals ?? []) as $signal)
                            <span class="rounded-2xl bg-ink-50 px-2.5 py-1 text-xs font-medium text-ink-700">{{ $signal }}</span>
                        @empty
                            <p class="text-sm text-ink-500">Sem sinais registados.</p>
                        @endforelse
                    </div>
                </section>

                <section class="mv-surface overflow-hidden">
                    <div class="border-b border-ink-100 px-6 py-4">
                        <h2 class="text-lg font-semibold text-ink-900">Campos estruturados</h2>
                    </div>
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Campo</th>
                                <th class="px-5 py-3">Valor</th>
                                <th class="px-5 py-3">Confiança</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($analysis->fields as $field)
                                <tr>
                                    <td class="px-5 py-4 font-semibold">{{ $field->label ?? $field->key }}</td>
                                    <td class="px-5 py-4">{{ $field->value ?? '-' }}</td>
                                    <td class="px-5 py-4">{{ $field->confidence !== null ? number_format((float) $field->confidence * 100, 0).'%' : '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-8 text-center text-ink-500">Sem campos extraídos nesta sprint.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Texto OCR</h2>
                    @if ($canViewSensitive && $analysis->ocr_text)
                        <pre class="mt-4 max-h-96 overflow-auto rounded-2xl bg-ink-950 p-4 text-xs leading-6 text-white">{{ \Illuminate\Support\Str::limit($analysis->ocr_text, 6000) }}</pre>
                    @else
                        <p class="mt-3 text-sm text-ink-500">Texto OCR sensível oculto. Apenas perfis com permissão de auditoria documental podem consultar o excerto técnico.</p>
                    @endif
                </section>
            </div>

            <aside class="space-y-6">
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Documento</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-semibold text-ink-500">Tipo documental configurado</dt>
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
                    </dl>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Flags</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($analysis->flags as $flag)
                            <div class="mv-surface p-3">
                                <p class="font-semibold text-ink-900">{{ $flag->code }}</p>
                                <p class="mt-1 text-sm text-ink-600">{{ $flag->message }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-ink-500">Sem flags registadas.</p>
                        @endforelse
                    </div>
                </section>

                @can('markManualReview', $analysis)
                    <section class="mv-surface p-6">
                        <h2 class="text-lg font-semibold text-ink-900">Revisão manual</h2>
                        <form method="POST" action="{{ route('backoffice.document-ai.classifications.manual-review', $analysis) }}" class="mt-4 space-y-3">
                            @csrf
                            <textarea name="reason" rows="3" class="mv-input w-full text-sm" placeholder="Motivo da revisão manual"></textarea>
                            <button type="submit" class="mv-button-primary w-full justify-center">Marcar revisão manual</button>
                        </form>
                    </section>
                @endcan

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Execução</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($analysis->processingLogs->take(8) as $log)
                            <div class="border-l-2 border-mvhab-support/30 pl-3 text-sm">
                                <p class="font-semibold text-ink-900">{{ $log->step }}</p>
                                <p class="text-ink-500">{{ $log->message }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
