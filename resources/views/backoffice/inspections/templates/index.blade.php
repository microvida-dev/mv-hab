<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-ink-900">
                Modelos de checklist
            </h1>

            <a
                href="{{ route('backoffice.inspections.templates.create') }}"
                class="mv-button-primary"
            >
                Criar template
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-ui.table
            :headers="[
                'Template',
                'Tipo',
                'Itens',
            ]"
        >
            @forelse ($templates as $template)
                <tr>
                    <td>
                        <a
                            href="{{ route('backoffice.inspections.templates.edit', $template) }}"
                            class="font-semibold text-mvhab-primary"
                        >
                            {{ $template->name }}
                        </a>
                    </td>

                    <td>
                        {{ $template->inspection_type?->label() ?? '-' }}
                    </td>

                    <td>
                        {{ $template->items->count() }} itens
                    </td>
                </tr>
            @empty
                <x-ui.table-empty
                    :colspan="3"
                    message="Sem modelos de checklist."
                />
            @endforelse
        </x-ui.table>

        <div>
            {{ $templates->links() }}
        </div>
    </div>
</x-app-layout>
