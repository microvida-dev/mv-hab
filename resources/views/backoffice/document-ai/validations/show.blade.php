<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-ink-900">Validação IA da candidatura</h1>
                <p class="text-sm text-ink-500">{{ $application->application_number ?? 'Candidatura sem número' }} · {{ $application->user?->name ?? 'Candidato não identificado' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('backoffice.document-ai.validations.index') }}" class="mv-button-secondary">Voltar</a>
                <form method="POST" action="{{ route('backoffice.document-ai.validations.rerun', $application) }}">
                    @csrf
                    <input type="hidden" name="confirm_reprocess" value="1">
                    <button type="submit" class="mv-button-primary">Reprocessar</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_340px] lg:px-8">
            <div class="space-y-6">
                @include('backoffice.document-ai.validations._summary', ['summary' => $summary])
                @include('backoffice.document-ai.validations._validation-table', ['presentedValidations' => $presentedValidations])
            </div>

            <aside class="space-y-6">
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Execução</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-semibold text-ink-500">Estado</dt>
                            <dd class="text-ink-900">{{ $run?->status?->label() ?? 'Sem execução' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">Revisão manual</dt>
                            <dd class="{{ $run?->requires_manual_review ? 'text-amber-700' : 'text-civic-700' }}">
                                {{ $run?->requires_manual_review ? 'Necessária' : 'Não necessária' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-ink-500">Concluída em</dt>
                            <dd class="text-ink-900">{{ $run?->completed_at?->format('d/m/Y H:i') ?? '-' }}</dd>
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
            </aside>
        </div>
    </div>
</x-app-layout>
