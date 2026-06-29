<x-ui.table :headers="['Indicador','Categoria','Tipo','Estado']">
    @forelse ($indicators as $indicator)
        <tr>
            <td>
                <a class="font-semibold text-mvhab-primary"
                   href="{{ route('backoffice.reports.indicators.show',$indicator) }}">
                    {{ $indicator->name }}
                </a>
                <p class="text-xs text-ink-500">
                    {{ $indicator->code }}
                </p>
            </td>

            <td>{{ $indicator->category->label() }}</td>

            <td>{{ $indicator->value_type->label() }}</td>

            <td>{{ $indicator->is_active ? 'Ativo' : 'Inativo' }}</td>
        </tr>
    @empty
        <x-ui.table-empty :colspan="4" message="Sem indicadores." />
    @endforelse
</x-ui.table>
