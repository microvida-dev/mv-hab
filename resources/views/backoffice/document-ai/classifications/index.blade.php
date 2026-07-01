@php
    use App\Enums\DocumentAiClassificationStatus;
    use App\Enums\DocumentAiDocumentType;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold text-ink-900">Classificação IA documental</h1>
            <p class="text-sm text-ink-500">Triagem automática por OCR, palavras-chave, layout e IA local.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('backoffice.document-ai.classifications.index') }}" class="mv-surface mb-6 grid gap-4 p-5 md:grid-cols-3 xl:grid-cols-6">
                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Tipo</span>
                    <select name="document_type" class="mv-input w-full">
                        <option value="">Todos</option>
                        @foreach (DocumentAiDocumentType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(($filters['document_type'] ?? null) === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Estado</span>
                    <select name="classification_status" class="mv-input w-full">
                        <option value="">Todos</option>
                        @foreach (DocumentAiClassificationStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(($filters['classification_status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">OCR</span>
                    <select name="ocr_available" class="mv-input w-full">
                        <option value="">Todos</option>
                        <option value="1" @selected(($filters['ocr_available'] ?? null) === '1')>Disponível</option>
                        <option value="0" @selected(($filters['ocr_available'] ?? null) === '0')>Indisponível</option>
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Revisão</span>
                    <select name="requires_manual_review" class="mv-input w-full">
                        <option value="">Todas</option>
                        <option value="1" @selected(($filters['requires_manual_review'] ?? null) === '1')>Requer revisão</option>
                        <option value="0" @selected(($filters['requires_manual_review'] ?? null) === '0')>Sem revisão</option>
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Confiança mín.</span>
                    <input type="number" step="0.01" min="0" max="1" name="min_confidence" value="{{ $filters['min_confidence'] ?? '' }}" class="mv-input w-full">
                </label>

                <div class="flex items-end gap-2">
                    <button type="submit" class="mv-button-primary w-full justify-center">Filtrar</button>
                    <a href="{{ route('backoffice.document-ai.classifications.index') }}" class="mv-button-secondary justify-center">Limpar</a>
                </div>
            </form>

            <section class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr>
                            <th class="px-5 py-3">Documento</th>
                            <th class="px-5 py-3">Classificação IA</th>
                            <th class="px-5 py-3">Confiança</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">OCR</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($analyses as $analysis)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-ink-900">{{ $analysis->documentSubmission?->title ?? 'Documento sem título' }}</p>
                                    <p class="text-xs text-ink-500">{{ $analysis->documentSubmission?->documentType?->name ?? $analysis->source_mime ?? 'Tipo não indicado' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-mvhab-primary">{{ $analysis->detected_document_label ?? 'Por classificar' }}</p>
                                    <p class="text-xs text-ink-500">{{ $analysis->classification_source ?? 'Sem fonte' }}</p>
                                </td>
                                <td class="px-5 py-4">{{ $analysis->classification_confidence !== null ? number_format((float) $analysis->classification_confidence * 100, 0).'%' : '-' }}</td>
                                <td class="px-5 py-4">{{ $analysis->classification_status?->label() ?? $analysis->status->label() }}</td>
                                <td class="px-5 py-4">
                                    <span class="{{ $analysis->ocr_available ? 'text-mvhab-primary' : 'text-amber-700' }}">
                                        {{ $analysis->ocr_available ? 'Disponível' : 'Indisponível' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.document-ai.classifications.show', $analysis) }}">Ver detalhe</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-ink-500">Sem análises documentais para os filtros selecionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <div class="mt-4">
                {{ $analyses->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
