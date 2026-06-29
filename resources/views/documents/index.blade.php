<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-ink-900">Documentos</h2>
            <a href="{{ route('documents.create') }}" class="mv-button-primary">
                Novo documento
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="overflow-hidden mv-surface">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-mvhab-surface">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Nome</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Munícipe</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Candidatura</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Contrato</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Tamanho</th>
                                <th class="px-4 py-3 text-right font-semibold text-ink-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($documents as $document)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-ink-900">{{ $document->name }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $document->citizen?->name ?: 'Sem associação' }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $document->housingApplication ? '#'.$document->housingApplication->id : 'Sem associação' }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $document->contract ? '#'.$document->contract->id : 'Sem associação' }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ number_format($document->size / 1024, 1, ',', '.') }} KB</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('documents.show', $document) }}" class="rounded-2xl border border-mvhab-support/40 px-3 py-1.5 text-xs font-semibold text-mvhab-primary hover:bg-mvhab-surface">Ver</a>
                                            <a href="{{ route('documents.edit', $document) }}" class="rounded-2xl border border-mvhab-support/40 px-3 py-1.5 text-xs font-semibold text-mvhab-primary hover:bg-mvhab-surface">Editar</a>
                                            <form method="POST" action="{{ route('documents.destroy', $document) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-2xl border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminar este documento?')">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-ink-500">Ainda não existem documentos registados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-ink-100 px-4 py-4">
                    {{ $documents->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
