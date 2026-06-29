<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Área pessoal</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Contratos</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <x-ui.table :headers="['Número', 'Habitação', 'Estado', 'Início', 'Fim', 'Renda', 'Caução', '']">
                @forelse ($contracts as $contract)
                    <tr>
                        <td class="font-semibold">{{ $contract->contract_number }}</td>
                        <td>{{ $contract->housingUnit?->code }}</td>
                        <td>{{ $contract->status->label() }}</td>
                        <td>{{ $contract->start_date?->format('d/m/Y') }}</td>
                        <td>{{ $contract->end_date?->format('d/m/Y') }}</td>
                        <td>{{ $contract->monthly_rent }}</td>
                        <td>{{ $contract->deposit?->amount ?? '-' }}</td>

                        <x-ui.table-actions>
                            <a class="font-semibold text-mvhab-primary" href="{{ route('candidate.contracts.show', $contract) }}">
                                Abrir
                            </a>
                        </x-ui.table-actions>
                    </tr>
                @empty
                    <x-ui.table-empty :colspan="8" message="Ainda não existem contratos disponíveis." />
                @endforelse
            </x-ui.table>

            <div class="mt-4">
                {{ $contracts->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
