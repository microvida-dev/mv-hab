<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-ink-900">
                Fornecedores de manutenção
            </h1>

            <a
                href="{{ route('backoffice.maintenance.suppliers.create') }}"
                class="mv-button-primary"
            >
                Criar fornecedor
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-ui.table
            :headers="[
                'Fornecedor',
                'Estado',
                'Âmbito',
            ]"
        >
            @forelse ($suppliers as $supplier)
                <tr>
                    <td>
                        <a
                            href="{{ route('backoffice.maintenance.suppliers.show', $supplier) }}"
                            class="font-semibold text-mvhab-primary"
                        >
                            {{ $supplier->name }}
                        </a>
                    </td>

                    <td>{{ $supplier->status }}</td>

                    <td>{{ $supplier->service_scope }}</td>
                </tr>
            @empty
                <x-ui.table-empty
                    :colspan="3"
                    message="Sem fornecedores de manutenção."
                />
            @endforelse
        </x-ui.table>

        <div>
            {{ $suppliers->links() }}
        </div>
    </div>
</x-app-layout>
