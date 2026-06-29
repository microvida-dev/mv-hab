<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-ink-900">
                Os meus pedidos de manutenção
            </h1>

            <a class="mv-button-primary" href="{{ route('candidate.maintenance.requests.create') }}">
                Novo pedido
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-ui.table :headers="['Número', 'Título', 'Estado']">
            @forelse ($maintenanceRequests as $request)
                <tr>
                    <td>
                        <a class="font-semibold text-mvhab-primary" href="{{ route('candidate.maintenance.requests.show', $request) }}">
                            {{ $request->request_number }}
                        </a>
                    </td>
                    <td>{{ $request->title }}</td>
                    <td>{{ $request->status->label() }}</td>
                </tr>
            @empty
                <x-ui.table-empty :colspan="3" message="Sem pedidos de manutenção." />
            @endforelse
        </x-ui.table>

        <div>
            {{ $maintenanceRequests->links() }}
        </div>
    </div>
</x-app-layout>
