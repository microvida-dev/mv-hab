<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-ink-900">Manutenção</h2>
            <a href="{{ route('maintenance-requests.create') }}" class="mv-button-primary">
                Novo pedido
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
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Título</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Habitação</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Munícipe</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Prioridade</th>
                                <th class="px-4 py-3 text-left font-semibold text-ink-600">Estado</th>
                                <th class="px-4 py-3 text-right font-semibold text-ink-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($maintenanceRequests as $maintenanceRequest)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-ink-900">{{ $maintenanceRequest->title }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $maintenanceRequest->housingUnit->code }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $maintenanceRequest->citizen?->name ?: 'Sem munícipe' }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $maintenanceRequest->priority->label() }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $maintenanceRequest->status->label() }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('maintenance-requests.show', $maintenanceRequest) }}" class="rounded-2xl border border-mvhab-support/40 px-3 py-1.5 text-xs font-medium text-ink-700 hover:bg-mvhab-surface">Ver</a>
                                            <a href="{{ route('maintenance-requests.edit', $maintenanceRequest) }}" class="rounded-2xl border border-mvhab-support/40 px-3 py-1.5 text-xs font-medium text-ink-700 hover:bg-mvhab-surface">Editar</a>
                                            <form method="POST" action="{{ route('maintenance-requests.destroy', $maintenanceRequest) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-2xl border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50" onclick="return confirm('Eliminar este pedido?')">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-ink-500">Ainda não existem pedidos de manutenção registados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-ink-100 px-4 py-4">
                    {{ $maintenanceRequests->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
