<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Os meus pedidos de manutenção</h1></x-slot>
    <a class="mv-button-primary" href="{{ route('candidate.maintenance.requests.create') }}">Novo pedido</a>
    <div class="mv-card mt-4 overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($maintenanceRequests as $request)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('candidate.maintenance.requests.show', $request) }}">{{ $request->request_number }}</a></td><td>{{ $request->title }}</td><td>{{ $request->status->label() }}</td></tr>@endforeach</tbody></table>{{ $maintenanceRequests->links() }}</div>
</x-app-layout>
