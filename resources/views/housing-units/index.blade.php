<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-ink-900">
                Habitações
            </h2>

            <a href="{{ route('housing-units.create') }}" class="mv-button-primary">
                Nova habitação
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-ui.table
            :headers="[
                'Código',
                'Morada',
                'Tipologia',
                'Renda',
                'Estado',
                'Ações',
            ]"
        >
            @forelse ($housingUnits as $housingUnit)
                <tr>
                    <td class="font-semibold text-ink-900">
                        {{ $housingUnit->code }}
                    </td>

                    <td>{{ $housingUnit->address }}</td>

                    <td>
                        {{ $housingUnit->typology }} / {{ $housingUnit->bedrooms }} quartos
                    </td>

                    <td>
                        {{ number_format((float) $housingUnit->monthly_rent, 2, ',', '.') }} €
                    </td>

                    <td>
                        {{ $housingUnit->status->label() }}
                    </td>

                    <x-ui.table-actions>
                        <a
                            href="{{ route('housing-units.show', $housingUnit) }}"
                            class="font-semibold text-mvhab-primary"
                        >
                            Ver
                        </a>

                        <a
                            href="{{ route('housing-units.edit', $housingUnit) }}"
                            class="font-semibold text-mvhab-primary"
                        >
                            Editar
                        </a>

                        <form
                            method="POST"
                            action="{{ route('housing-units.destroy', $housingUnit) }}"
                            onsubmit="return confirm('Eliminar esta habitação?')"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="font-semibold text-danger-700"
                            >
                                Eliminar
                            </button>
                        </form>
                    </x-ui.table-actions>
                </tr>
            @empty
                <x-ui.table-empty
                    :colspan="6"
                    message="Ainda não existem habitações registadas."
                />
            @endforelse
        </x-ui.table>

        <div>
            {{ $housingUnits->links() }}
        </div>
    </div>
</x-app-layout>
