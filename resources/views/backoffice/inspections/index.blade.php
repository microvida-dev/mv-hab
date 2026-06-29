<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-ink-900">
                Vistorias
            </h1>

            <a
                href="{{ route('backoffice.inspections.create') }}"
                class="mv-button-primary"
            >
                Criar vistoria
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
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

                    <td>{{ $inspection->status->label() }}</td>

                    <td>{{ $inspection->scheduled_for?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <x-ui.table-empty
                    :colspan="5"
                    message="Sem vistorias."
                />
            @endforelse
        </x-ui.table>

        <div>
            {{ $inspections->links() }}
        </div>
    </div>
</x-app-layout>
