<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Munícipes</h2>
            <a href="{{ route('citizens.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                Novo munícipe
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
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Documento</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Contacto</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Agregados</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Candidaturas</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($citizens as $citizen)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $citizen->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $citizen->document_number }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <div>{{ $citizen->phone ?: 'Sem telefone' }}</div>
                                        <div class="text-xs text-slate-500">{{ $citizen->email ?: 'Sem email' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $citizen->households_count }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $citizen->housing_applications_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('citizens.show', $citizen) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Ver</a>
                                            <a href="{{ route('citizens.edit', $citizen) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</a>
                                            <form method="POST" action="{{ route('citizens.destroy', $citizen) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminar este munícipe?')">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">Ainda não existem munícipes registados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-4">
                    {{ $citizens->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
