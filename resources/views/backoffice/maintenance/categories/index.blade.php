<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Categorias de manutenção"
            description="Gerir categorias e urgência por defeito dos pedidos de manutenção."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.maintenance.categories.create') }}" class="mv-button-primary">
                    Criar categoria
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-mv.section padding="p-0" class="overflow-hidden">
            <x-ui.table
                :headers="[
                    'Código',
                    'Nome',
                    'Urgência',
                    '',
                ]"
            >
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $category->code }}</td>

                        <td>{{ $category->name }}</td>

                        <td>{{ $category->default_urgency?->label() ?? '-' }}</td>

                        <x-ui.table-actions>
                            <a
                                href="{{ route('backoffice.maintenance.categories.edit', $category) }}"
                                class="font-semibold text-mvhab-primary"
                            >
                                Editar
                            </a>
                        </x-ui.table-actions>
                    </tr>
                @empty
                    <x-ui.table-empty
                        :colspan="4"
                        message="Sem categorias de manutenção."
                    />
                @endforelse
            </x-ui.table>
        </x-mv.section>

        <div>
            {{ $categories->links() }}
        </div>
    </div>
</x-app-layout>
