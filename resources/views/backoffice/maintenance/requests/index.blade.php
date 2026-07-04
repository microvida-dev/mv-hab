<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Pedidos de manutenção"
            description="Acompanhe triagem, execução e fecho dos pedidos."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.maintenance.requests.create') }}" class="mv-button-primary">
                    Criar pedido
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-mv.section padding="p-0" class="overflow-hidden">
            <x-ui.table
                :headers="[
                    'Número',
                    'Habitação',
                    'Categoria',
                    'Urgência',
                    'Estado',
                    'Data',
                ]"
            >
                @forelse ($maintenanceRequests as $request)
                    <tr>
                        <td>
                            <a
                                href="{{ route('backoffice.maintenance.requests.show', $request) }}"
                                class="font-semibold text-mvhab-primary"
                            >
                                {{ $request->request_number ?? '#'.$request->id }}
                            </a>
                        </td>

                        <td>{{ $request->housingUnit?->code }}</td>

                        <td>{{ $request->category?->name ?? '-' }}</td>

                        <td>{{ $request->urgency?->label() ?? '-' }}</td>

                        <td><x-mv.badge>{{ $request->status?->label() }}</x-mv.badge></td>

                        <td>{{ $request->reported_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <x-ui.table-empty
                        :colspan="6"
                        message="Sem pedidos de manutenção."
                    />
                @endforelse
            </x-ui.table>
        </x-mv.section>

        <div>
            {{ $maintenanceRequests->links() }}
        </div>
    </div>
</x-app-layout>
