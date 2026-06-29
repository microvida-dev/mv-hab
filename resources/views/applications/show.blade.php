<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Candidatura #{{ $application->id }}</h2>
            <a href="{{ route('applications.edit', $application) }}" class="mv-button-secondary">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="mv-surface p-6 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-slate-900">Dados da candidatura</h3>
                    <dl class="mt-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Munícipe</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $application->citizen->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Agregado</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $application->household?->name ?: 'Sem agregado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Estado</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $application->status->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Pontuação de prioridade</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $application->priority_score }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Submetida em</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $application->submitted_at?->format('d/m/Y H:i') ?: 'Por submeter' }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-slate-500">Notas</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $application->notes ?: 'Sem notas.' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mv-surface p-6">
                    <h3 class="text-lg font-semibold text-slate-900">Documentos</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($application->documents as $document)
                            <a href="{{ route('documents.show', $document) }}" class="block rounded-2xl border border-ink-100 p-4 hover:bg-mvhab-surface">
                                <p class="font-medium text-slate-900">{{ $document->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $document->mime_type }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">Sem documentos associados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
