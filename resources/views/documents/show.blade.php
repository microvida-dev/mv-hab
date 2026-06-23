<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ $document->name }}</h2>
            <a href="{{ route('documents.edit', $document) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Dados do documento</h3>
                <dl class="mt-6 grid gap-6 md:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Munícipe</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $document->citizen?->name ?: 'Sem associação' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Candidatura</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $document->housingApplication ? '#'.$document->housingApplication->id : 'Sem associação' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Contrato</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $document->contract ? '#'.$document->contract->id : 'Sem associação' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Tipo MIME</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $document->mime_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Tamanho</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ number_format($document->size / 1024, 1, ',', '.') }} KB</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-slate-500">Caminho</dt>
                        <dd class="mt-1 break-all text-sm text-slate-900">{{ $document->path }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
