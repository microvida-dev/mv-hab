<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Vistorias"
            title="Modelos de checklist"
            description="Gerir modelos de checklist reutilizados em vistorias municipais."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.inspections.templates.create') }}" class="mv-button-primary">
                    Criar template
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-mv.section padding="p-0" class="overflow-hidden">
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
        </x-mv.section>

        <div>
            {{ $templates->links() }}
        </div>
    </div>
</x-app-layout>
