<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Custos de manutenção</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left text-ink-500"><th>Pedido</th><th>Imóvel</th><th>Tipo</th><th>Valor</th><th>Estado</th></tr></thead><tbody>@foreach ($costs as $cost)<tr class="border-t border-ink-100"><td class="py-2">{{ $cost->maintenanceRequest?->request_number }}</td><td>{{ $cost->housingUnit?->code }}</td><td>{{ $cost->cost_type?->label() }}</td><td>{{ number_format((float) $cost->amount, 2, ',', '.') }} {{ $cost->currency }}</td><td>{{ $cost->status?->label() }}</td></tr>@endforeach</tbody></table>{{ $costs->links() }}</div>
</x-app-layout>
