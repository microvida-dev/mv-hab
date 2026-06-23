<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-ink-900">Assistente IA documental</h1>
                <p class="text-sm text-ink-500">Análise #{{ $analysis->id }} · {{ $analysis->documentSubmission?->documentType?->name ?? $analysis->detected_document_type?->label() ?? 'Tipo documental não identificado' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('backoffice.document-ai.assistant.index') }}" class="mv-button-secondary">Voltar</a>
                <form method="POST" action="{{ route('backoffice.document-ai.assistant.recalculate', $analysis) }}">
                    @csrf
                    <input type="hidden" name="confirm_recalculate" value="1">
                    <button type="submit" class="mv-button-primary">Recalcular</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
            <div class="space-y-6">
                <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    O score IA e as flags são auxiliares à análise técnica e não produzem decisão automática sobre a candidatura.
                </div>

                @include('backoffice.document-ai.assistant._score-card', ['score' => $score])
                @include('backoffice.document-ai.assistant._flags', ['flags' => $flags])
                @include('backoffice.document-ai.assistant._suggestions', ['suggestions' => $suggestions])
            </div>

            <aside class="space-y-6">
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Documento</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-semibold text-ink-500">Análise</dt>
                            <dd class="text-ink-900">#{{ $analysis->id }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">Candidatura</dt>
                            <dd class="text-ink-900">{{ $analysis->documentSubmission?->application?->application_number ?? 'Sem candidatura direta' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">OCR</dt>
                            <dd class="{{ $analysis->ocr_available ? 'text-civic-700' : 'text-amber-700' }}">
                                {{ $analysis->ocr_available ? 'Disponível' : 'Indisponível' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">Classificação</dt>
                            <dd class="text-ink-900">{{ $analysis->detected_document_type?->label() ?? 'Não classificado' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Limites da IA</h2>
                    <p class="mt-3 text-sm leading-6 text-ink-600">
                        As sugestões ficam em rascunho operacional. O técnico decide se edita, aceita ou descarta cada recomendação.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
