<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Revisão documental</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $submission->documentType->name }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $submission->adhesionRegistration?->full_name ?? 'Candidato não indicado' }}</p>
            </div>
            <a href="{{ route('admin.document-reviews.download', $submission) }}" class="mv-button-secondary">Download seguro</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @php
                $latestAiAnalysis = $submission->latestDocumentAiAnalysis;
                $latestAiScore = $latestAiAnalysis?->latestScore;
            @endphp

            <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-6">
                    <div class="mv-surface p-6">
                        <h2 class="text-base font-semibold text-ink-900">Detalhe do documento</h2>
                        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase text-ink-500">Estado</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $submission->status->label() }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-ink-500">Ficheiro</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $submission->original_filename }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-ink-500">Submetido</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ optional($submission->submitted_at)->format('d/m/Y H:i') ?: 'Não indicado' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-ink-500">Checksum</dt>
                                <dd class="mt-1 break-all text-xs font-semibold text-ink-700">{{ $submission->checksum }}</dd>
                            </div>
                        </dl>
                        @if ($submission->rejection_reason)
                            <p class="mt-5 rounded-md bg-red-50 p-4 text-sm leading-6 text-red-800">{{ $submission->rejection_reason }}</p>
                        @endif
                    </div>

                    <div class="mv-surface p-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-ink-900">IA documental</h2>
                                <p class="mt-1 text-sm leading-6 text-ink-600">
                                    A análise IA é assistiva e não valida nem rejeita documentos automaticamente.
                                </p>
                            </div>
                            @if ($latestAiAnalysis)
                                <a href="{{ route('backoffice.document-ai.assistant.show', $latestAiAnalysis) }}" class="mv-button-secondary">Ver assistente IA</a>
                            @endif
                        </div>
                        <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                            <div>
                                <dt class="text-xs font-semibold uppercase text-ink-500">Estado IA</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $latestAiAnalysis?->status->label() ?? 'Sem análise executada' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-ink-500">Score</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $latestAiScore ? $latestAiScore->score.'%' : 'Sem score' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase text-ink-500">Revisão manual</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $latestAiScore?->requires_manual_review ? 'Recomendada' : 'Não indicada' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <aside class="mv-surface p-6">
                    <h2 class="text-base font-semibold text-ink-900">Ações</h2>
                    <div class="mt-4 grid gap-3">
                        <form method="POST" action="{{ route('admin.document-reviews.document-ai', $submission) }}">
                            @csrf
                            <button class="mv-button-primary w-full">Executar IA documental</button>
                            <p class="mt-2 text-xs leading-5 text-ink-500">Gera score, flags e sugestões para apoio à revisão técnica.</p>
                        </form>
                        <form method="POST" action="{{ route('admin.document-reviews.under-review', $submission) }}">
                            @csrf
                            <button class="mv-button-secondary w-full">Colocar em análise</button>
                        </form>
                        <form method="POST" action="{{ route('admin.document-reviews.validate', $submission) }}">
                            @csrf
                            <textarea name="internal_notes" rows="3" class="mb-2 block w-full rounded-md border-ink-300 text-sm shadow-sm focus:border-civic-700 focus:ring-civic-700" placeholder="Notas internas opcionais"></textarea>
                            <button class="mv-button-primary w-full">Validar documento</button>
                        </form>
                        <form method="POST" action="{{ route('admin.document-reviews.reject', $submission) }}" class="space-y-2">
                            @csrf
                            <textarea name="rejection_reason" rows="3" class="block w-full rounded-md border-ink-300 text-sm shadow-sm focus:border-civic-700 focus:ring-civic-700" placeholder="Motivo visível ao candidato" required></textarea>
                            <textarea name="internal_notes" rows="3" class="block w-full rounded-md border-ink-300 text-sm shadow-sm focus:border-civic-700 focus:ring-civic-700" placeholder="Notas internas opcionais"></textarea>
                            <button class="mv-button-secondary w-full">Rejeitar com motivo</button>
                        </form>
                    </div>
                    <x-input-error class="mt-3" :messages="$errors->get('rejection_reason')" />
                    <x-input-error class="mt-3" :messages="$errors->get('internal_notes')" />
                </aside>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="mv-surface p-6">
                    <h2 class="text-base font-semibold text-ink-900">Versões</h2>
                    <div class="mt-4 divide-y divide-ink-100">
                        @foreach ($submission->versions->sortByDesc('version_number') as $version)
                            <div class="py-3">
                                <p class="font-semibold text-ink-900">Versão {{ $version->version_number }}</p>
                                <p class="text-sm text-ink-500">{{ $version->original_filename }} · {{ $version->status_at_upload->label() }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mv-surface p-6">
                    <h2 class="text-base font-semibold text-ink-900">Decisões e acessos</h2>
                    <div class="mt-4 divide-y divide-ink-100">
                        @forelse ($submission->reviews->sortByDesc('created_at') as $review)
                            <div class="py-3">
                                <p class="font-semibold text-ink-900">{{ $review->decision->label() }}</p>
                                <p class="text-sm text-ink-500">{{ $review->created_at->format('d/m/Y H:i') }} · {{ $review->reviewedBy?->name ?? 'Sistema' }}</p>
                                @if ($review->internal_notes)
                                    <p class="mt-2 text-sm leading-6 text-ink-600">{{ $review->internal_notes }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-ink-600">Sem decisões registadas.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
