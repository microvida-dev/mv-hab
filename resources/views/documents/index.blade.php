<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Documentos</h2>
            <a href="{{ route('documents.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                Novo documento
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Nome</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Munícipe</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Candidatura</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Contrato</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tamanho</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($documents as $document)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $document->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $document->citizen?->name ?: 'Sem associação' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $document->housingApplication ? '#'.$document->housingApplication->id : 'Sem associação' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $document->contract ? '#'.$document->contract->id : 'Sem associação' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ number_format($document->size / 1024, 1, ',', '.') }} KB</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('documents.show', $document) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Ver</a>
                                            <a href="{{ route('documents.edit', $document) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</a>
                                            <form method="POST" action="{{ route('documents.destroy', $document) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminar este documento?')">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">Ainda não existem documentos registados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-4">
                    {{ $documents->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
