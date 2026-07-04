<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Área pessoal"
            title="Contratos"
            description="Consulte os contratos disponibilizados pelos serviços municipais."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.section padding="p-0" class="overflow-hidden">
                <x-ui.table :headers="['Número', 'Habitação', 'Estado', 'Início', 'Fim', 'Renda', 'Caução', '']">
                    @forelse ($contracts as $contract)
                        <tr>
                            <td class="font-semibold">{{ $contract->contract_number }}</td>
                            <td>{{ $contract->housingUnit?->code }}</td>
                            <td><x-mv.badge>{{ $contract->status->label() }}</x-mv.badge></td>
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
            </x-mv.section>

            {{ $contracts->links() }}
        </div>
    </div>
</x-app-layout>
