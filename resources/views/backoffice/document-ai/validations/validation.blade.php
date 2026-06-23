<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-ink-900">Detalhe da validação IA</h1>
                <p class="text-sm text-ink-500">{{ $validation->label }}</p>
            </div>
            @if ($validation->application)
                <a href="{{ route('backoffice.document-ai.validations.show', $validation->application) }}" class="mv-button-secondary">Voltar</a>
            @else
                <a href="{{ route('backoffice.document-ai.validations.index') }}" class="mv-button-secondary">Voltar</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
            <div class="space-y-6">
                <section class="mv-surface p-6">
                    <div class="grid gap-5 md:grid-cols-4">
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Grupo</p>
                            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $presentedValidation['group'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Estado</p>
                            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $presentedValidation['status'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Severidade</p>
                            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $presentedValidation['severity'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-ink-500">Confiança</p>
                            <p class="mt-1 text-lg font-semibold text-ink-900">{{ $presentedValidation['confidence'] !== null ? number_format($presentedValidation['confidence'] * 100, 0).'%' : '-' }}</p>
                        </div>
                    </div>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Cruzamento</h2>
                    <dl class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase text-ink-500">Valor declarado</dt>
                            <dd class="mt-1 text-base font-semibold text-ink-900">{{ $presentedValidation['candidate_value'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-ink-500">Valor extraído</dt>
                            <dd class="mt-1 text-base font-semibold text-ink-900">{{ $presentedValidation['extracted_value'] }}</dd>
                        </div>
                    </dl>
                    <p class="mt-5 text-sm text-ink-600">{{ $presentedValidation['message'] }}</p>
                    @if ($presentedValidation['recommendation'])
                        <p class="mt-3 text-sm font-semibold text-amber-700">{{ $presentedValidation['recommendation'] }}</p>
                    @endif
                </section>
            </div>

            <aside class="space-y-6">
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Documento</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-semibold text-ink-500">Tipo</dt>
                            <dd class="text-ink-900">{{ $validation->analysis?->detected_document_label ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">Documento submetido</dt>
                            <dd class="text-ink-900">{{ $validation->analysis?->documentSubmission?->title ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">Revisão</dt>
                            <dd class="{{ $validation->requires_manual_review ? 'text-amber-700' : 'text-civic-700' }}">
                                {{ $validation->requires_manual_review ? 'Necessária' : 'Não necessária' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Revisão manual</h2>
                    <form method="POST" action="{{ route('backoffice.document-ai.validations.manual-review', $validation) }}" class="mt-4 space-y-4">
                        @csrf
                        <label class="block space-y-1 text-sm">
                            <span class="font-semibold text-ink-700">Notas</span>
                            <textarea name="review_notes" rows="4" class="w-full rounded-md border-ink-200 text-sm">{{ old('review_notes', $validation->review_notes) }}</textarea>
                        </label>
                        <button type="submit" class="mv-button-primary w-full justify-center">Marcar revisão</button>
                    </form>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
