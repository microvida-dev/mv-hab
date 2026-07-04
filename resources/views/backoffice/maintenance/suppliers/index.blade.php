<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Fornecedores de manutenção"
            description="Gerir fornecedores e âmbito de prestação de serviços."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.maintenance.suppliers.create') }}" class="mv-button-primary">
                    Criar fornecedor
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-mv.section padding="p-0" class="overflow-hidden">
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

                        <td><x-mv.badge>{{ $supplier->status }}</x-mv.badge></td>

                        <td>{{ $supplier->service_scope }}</td>
                    </tr>
                @empty
                    <x-ui.table-empty
                        :colspan="3"
                        message="Sem fornecedores de manutenção."
                    />
                @endforelse
            </x-ui.table>
        </x-mv.section>

        <div>
            {{ $suppliers->links() }}
        </div>
    </div>
</x-app-layout>
