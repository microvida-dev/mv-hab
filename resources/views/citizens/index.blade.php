<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-ink-900">Munícipes</h2>
            <a href="{{ route('citizens.create') }}" class="mv-button-primary">
                Novo munícipe
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
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Documento</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Contacto</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Agregados</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Candidaturas</th>
                                <th class="px-4 py-3 text-right font-semibold text-ink-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($citizens as $citizen)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-ink-900">{{ $citizen->name }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $citizen->document_number }}</td>
                                    <td class="px-4 py-3 text-ink-600">
                                        <div>{{ $citizen->phone ?: 'Sem telefone' }}</div>
                                        <div class="text-xs text-ink-500">{{ $citizen->email ?: 'Sem email' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-ink-600">{{ $citizen->households_count }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $citizen->housing_applications_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('citizens.show', $citizen) }}" class="rounded-2xl border border-mvhab-support/40 px-3 py-1.5 text-xs font-medium text-ink-700 hover:bg-mvhab-surface">Ver</a>
                                            <a href="{{ route('citizens.edit', $citizen) }}" class="rounded-2xl border border-mvhab-support/40 px-3 py-1.5 text-xs font-medium text-ink-700 hover:bg-mvhab-surface">Editar</a>
                                            <form method="POST" action="{{ route('citizens.destroy', $citizen) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-2xl border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminar este munícipe?')">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-ink-500">Ainda não existem munícipes registados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-ink-100 px-4 py-4">
                    {{ $citizens->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
