@php
    use App\Enums\DocumentAiDocumentType;
    use App\Enums\DocumentAiExtractionStatus;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold text-ink-900">Extração IA documental</h1>
            <p class="text-sm text-ink-500">Campos estruturados extraídos por tipo documental, com revisão técnica controlada.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('backoffice.document-ai.extractions.index') }}" class="mv-surface mb-6 grid gap-4 p-5 md:grid-cols-3 xl:grid-cols-7">
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
                    <select name="extraction_status" class="mv-input w-full">
                        <option value="">Todos</option>
                        @foreach (DocumentAiExtractionStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(($filters['extraction_status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Revisão</span>
                    <select name="requires_review" class="mv-input w-full">
                        <option value="">Todas</option>
                        <option value="1" @selected(($filters['requires_review'] ?? null) === '1')>Requer revisão</option>
                        <option value="0" @selected(($filters['requires_review'] ?? null) === '0')>Sem revisão</option>
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Campo</span>
                    <input type="text" name="field_key" value="{{ $filters['field_key'] ?? '' }}" class="mv-input w-full" placeholder="nif, name, total_income">
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Confiança mín.</span>
                    <input type="number" step="0.01" min="0" max="1" name="min_confidence" value="{{ $filters['min_confidence'] ?? '' }}" class="mv-input w-full">
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Confiança máx.</span>
                    <input type="number" step="0.01" min="0" max="1" name="max_confidence" value="{{ $filters['max_confidence'] ?? '' }}" class="mv-input w-full">
                </label>

                <div class="flex items-end gap-2">
                    <button type="submit" class="mv-button-primary w-full justify-center">Filtrar</button>
                    <a href="{{ route('backoffice.document-ai.extractions.index') }}" class="mv-button-secondary justify-center">Limpar</a>
                </div>
            </form>

            <section class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr>
                            <th class="px-5 py-3">Documento</th>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">Confiança</th>
                            <th class="px-5 py-3">Campos</th>
                            <th class="px-5 py-3">Revisão</th>
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
                                    <p class="text-xs text-ink-500">{{ $analysis->extraction_schema_version ?? 'Sem schema' }}</p>
                                </td>
                                <td class="px-5 py-4">{{ $analysis->extraction_status?->label() ?? '-' }}</td>
                                <td class="px-5 py-4">{{ $analysis->extraction_confidence !== null ? number_format((float) $analysis->extraction_confidence * 100, 0).'%' : '-' }}</td>
                                <td class="px-5 py-4">{{ $analysis->extracted_fields_count ?? 0 }}</td>
                                <td class="px-5 py-4">
                                    <span class="{{ $analysis->extraction_requires_manual_review ? 'text-amber-700' : 'text-mvhab-primary' }}">
                                        {{ $analysis->extraction_requires_manual_review ? 'Requer revisão' : 'Sem revisão' }}
                                    </span>
                                    @if (($analysis->review_fields_count ?? 0) > 0)
                                        <p class="text-xs text-ink-500">{{ $analysis->review_fields_count }} campo(s)</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.document-ai.extractions.show', $analysis) }}">Ver extração</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-ink-500">Sem extrações estruturadas para os filtros selecionados.</td>
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
