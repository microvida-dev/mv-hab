<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Custos de manutenção"
            description="Consulte custos registados por pedido, imóvel, tipo e estado."
        />
    </x-slot>

    <x-mv.section padding="p-0" class="overflow-hidden">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-ink-500">
                    <th>Pedido</th>
                    <th>Imóvel</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($costs as $cost)
                    <tr class="border-t border-ink-100">
                        <td class="py-2">{{ $cost->maintenanceRequest?->request_number }}</td>
                        <td>{{ $cost->housingUnit?->code }}</td>
                        <td>{{ $cost->cost_type?->label() }}</td>
                        <td>{{ number_format((float) $cost->amount, 2, ',', '.') }} {{ $cost->currency }}</td>
                        <td><x-mv.badge>{{ $cost->status?->label() }}</x-mv.badge></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-mv.section>

    {{ $costs->links() }}
</x-app-layout>
