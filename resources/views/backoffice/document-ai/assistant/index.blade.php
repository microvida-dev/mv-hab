@php
    use App\Enums\DocumentAiRiskFlagCode;
    use App\Enums\DocumentAiScoreLabel;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold text-ink-900">Assistente IA documental</h1>
            <p class="text-sm text-ink-500">Score de confiança, indicadores de risco e sugestões internas para apoio à revisão técnica.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                O score IA e as flags são auxiliares à análise técnica e não produzem decisão automática sobre a candidatura.
            </div>

            <div class="mb-6 grid gap-4 md:grid-cols-4">
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Scores</p>
                    <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $totals['scores'] }}</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Revisão</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-700">{{ $totals['requires_review'] }}</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Baixa confiança</p>
                    <p class="mt-2 text-2xl font-semibold text-red-700">{{ $totals['low_confidence'] }}</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-xs font-semibold uppercase text-ink-500">Sugestões abertas</p>
                    <p class="mt-2 text-2xl font-semibold text-mvhab-primary">{{ $totals['open_suggestions'] }}</p>
                </div>
            </div>

            <form method="GET" action="{{ route('backoffice.document-ai.assistant.index') }}" class="mv-surface mb-6 grid gap-4 p-5 md:grid-cols-3 xl:grid-cols-7">
                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Score</span>
                    <select name="label" class="mv-input w-full">
                        <option value="">Todos</option>
                        @foreach (DocumentAiScoreLabel::cases() as $label)
                            <option value="{{ $label->value }}" @selected(($filters['label'] ?? null) === $label->value)>{{ $label->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Flag</span>
                    <select name="flag" class="mv-input w-full">
                        <option value="">Todas</option>
                        @foreach (DocumentAiRiskFlagCode::cases() as $flag)
                            <option value="{{ $flag->value }}" @selected(($filters['flag'] ?? null) === $flag->value)>{{ $flag->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-ink-700">Revisão</span>
                    <select name="requires_review" class="mv-input w-full">
                        <option value="">Todas</option>
                        <option value="1" @selected((string) ($filters['requires_review'] ?? '') === '1')>Requer revisão</option>
                        <option value="0" @selected((string) ($filters['requires_review'] ?? '') === '0')>Sem revisão</option>
                    </select>
                </label>

                <label class="space-y-1 text-sm xl:col-span-2">
                    <span class="font-semibold text-ink-700">Candidatura</span>
                    <input type="text" name="application" value="{{ $filters['application'] ?? '' }}" class="mv-input w-full" placeholder="Número ou referência">
                </label>

                <div class="flex items-end gap-2 xl:col-span-2">
                    <button type="submit" class="mv-button-primary w-full justify-center">Filtrar</button>
                    <a href="{{ route('backoffice.document-ai.assistant.index') }}" class="mv-button-secondary justify-center">Limpar</a>
                </div>
            </form>

            <section class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr>
                            <th class="px-5 py-3">Documento</th>
                            <th class="px-5 py-3">Candidatura</th>
                            <th class="px-5 py-3">Score IA</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3">Calculado em</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($scores as $score)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-ink-900">Análise #{{ $score->document_ai_analysis_id }}</p>
                                    <p class="text-xs text-ink-500">{{ $score->analysis?->documentSubmission?->documentType?->name ?? $score->analysis?->detected_document_type?->label() ?? 'Tipo documental não identificado' }}</p>
                                </td>
                                <td class="px-5 py-4 text-ink-700">{{ $score->application?->application_number ?? 'Sem candidatura associada' }}</td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-ink-900">{{ $score->score }}%</span>
                                    <span class="ml-2 text-xs text-ink-500">{{ $score->label->label() }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="{{ $score->requires_manual_review ? 'text-amber-700' : 'text-mvhab-primary' }}">
                                        {{ $score->requires_manual_review ? 'Rever manualmente' : 'Fluxo normal' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-ink-700">{{ $score->calculated_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.document-ai.assistant.score', $score) }}">Ver assistente</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-ink-500">Sem scores IA para os filtros selecionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <div class="mt-4">
                {{ $scores->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
