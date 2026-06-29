<x-ui.table :headers="['Nome','Relatório','Filtros','']">
    @forelse ($presets as $preset)
        <tr>

            <td>{{ $preset->name }}</td>

            <td>{{ $preset->definition->name }}</td>

            <td>
                {{ json_encode($preset->filters, JSON_UNESCAPED_UNICODE) }}
            </td>

            <td class="text-right">
                <form method="POST"
                      action="{{ route('backoffice.reports.filter-presets.destroy',$preset) }}">
                    @csrf
                    @method('DELETE')

                    <button
                        class="text-sm font-semibold text-danger-700">
                        Remover
                    </button>
                </form>
            </td>

        </tr>

    @empty

        <x-ui.table-empty
            :colspan="4"
            message="Sem filtros guardados."
        />

    @endforelse
</x-ui.table>
