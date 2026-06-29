<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-ink-900">
                Pedidos de manutenção
            </h1>

            <a
                href="{{ route('backoffice.maintenance.requests.create') }}"
                class="mv-button-primary"
            >
                Criar pedido
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
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

                    <td>{{ $request->status?->label() }}</td>

                    <td>{{ $request->reported_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <x-ui.table-empty
                    :colspan="6"
                    message="Sem pedidos de manutenção."
                />
            @endforelse
        </x-ui.table>

        <div>
            {{ $maintenanceRequests->links() }}
        </div>
    </div>
</x-app-layout>
