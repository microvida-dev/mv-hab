<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Habitações</h2>
            <a href="{{ route('housing-units.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                Nova habitação
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
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Código</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Morada</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tipologia</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Renda</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Estado</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($housingUnits as $housingUnit)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $housingUnit->code }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $housingUnit->address }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $housingUnit->typology }} / {{ $housingUnit->bedrooms }} quartos</td>
                                    <td class="px-4 py-3 text-slate-600">{{ number_format((float) $housingUnit->monthly_rent, 2, ',', '.') }} €</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $housingUnit->status->label() }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('housing-units.show', $housingUnit) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Ver</a>
                                            <a href="{{ route('housing-units.edit', $housingUnit) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</a>
                                            <form method="POST" action="{{ route('housing-units.destroy', $housingUnit) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminar esta habitação?')">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">Ainda não existem habitações registadas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-4">
                    {{ $housingUnits->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
