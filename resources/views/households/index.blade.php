<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Agregados familiares</h2>
            <a href="{{ route('households.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                Novo agregado
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
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Agregado</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Responsável</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Rendimento</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Membros</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Candidaturas</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($households as $household)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $household->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $household->citizen?->name ?? $household->adhesionRegistration?->full_name ?? 'Sem responsável associado' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ number_format((float) $household->monthly_income, 2, ',', '.') }} €</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $household->members_count }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $household->housing_applications_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('households.show', $household) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Ver</a>
                                            @if (! $household->isCandidateHousehold())
                                                <a href="{{ route('households.edit', $household) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</a>
                                                <form method="POST" action="{{ route('households.destroy', $household) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminar este agregado?')">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">Ainda não existem agregados familiares registados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-4">
                    {{ $households->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
