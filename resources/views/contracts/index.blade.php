<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Contratos</h2>
            <a href="{{ route('contracts.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                Novo contrato
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
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Munícipe</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Habitação</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Início</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Renda</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Estado</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($contracts as $contract)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $contract->citizen->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $contract->housingUnit->code }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $contract->start_date?->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ number_format((float) $contract->monthly_rent, 2, ',', '.') }} €</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $contract->status->label() }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('contracts.show', $contract) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Ver</a>
                                            <a href="{{ route('contracts.edit', $contract) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Editar</a>
                                            <form method="POST" action="{{ route('contracts.destroy', $contract) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminar este contrato?')">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">Ainda não existem contratos registados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-4">
                    {{ $contracts->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
