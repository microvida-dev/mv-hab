<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Património"
            title="Vistorias"
            description="Agende, acompanhe e valide vistorias aos fogos municipais."
        >
            <x-slot name="actions">
                <a href="{{ route('backoffice.inspections.create') }}" class="mv-button-primary">
                    Criar vistoria
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="space-y-6">
        <x-mv.section padding="p-0" class="overflow-hidden">
            <x-ui.table
                :headers="[
                    'Número',
                    'Tipo',
                    'Habitação',
                    'Estado',
                    'Data',
                ]"
            >
                @forelse ($inspections as $inspection)
                    <tr>
                        <td>
                            <a
                                href="{{ route('backoffice.inspections.show', $inspection) }}"
                                class="font-semibold text-mvhab-primary"
                            >
                                {{ $inspection->inspection_number }}
                            </a>
                        </td>

                        <td>{{ $inspection->inspection_type->label() }}</td>

                        <td>{{ $inspection->housingUnit?->code }}</td>

                        <td><x-mv.badge>{{ $inspection->status->label() }}</x-mv.badge></td>

                        <td>{{ $inspection->scheduled_for?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <x-ui.table-empty
                        :colspan="5"
                        message="Sem vistorias."
                    />
                @endforelse
            </x-ui.table>
        </x-mv.section>

        <div>
            {{ $inspections->links() }}
        </div>
    </div>
</x-app-layout>
