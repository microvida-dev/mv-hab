<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-ink-900">
                Categorias de manutenção
            </h1>

            <a
                href="{{ route('backoffice.maintenance.categories.create') }}"
                class="mv-button-primary"
            >
                Criar categoria
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
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

        <div>
            {{ $categories->links() }}
        </div>
    </div>
</x-app-layout>
