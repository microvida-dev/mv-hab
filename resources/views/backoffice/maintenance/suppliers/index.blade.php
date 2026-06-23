<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Fornecedores de manutenção</h1></x-slot>
    <a class="mv-button-primary" href="{{ route('backoffice.maintenance.suppliers.create') }}">Criar fornecedor</a>
    <div class="mv-card mt-4 overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($suppliers as $supplier)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('backoffice.maintenance.suppliers.show', $supplier) }}">{{ $supplier->name }}</a></td><td>{{ $supplier->status }}</td><td>{{ $supplier->service_scope }}</td></tr>@endforeach</tbody></table>{{ $suppliers->links() }}</div>
</x-app-layout>
