<x-ui.table :headers="['Título','Indicador','Ordem']">
    @forelse ($dashboard->widgets as $widget)
        <tr>
            <td>{{ $widget->title }}</td>

            <td>{{ $widget->indicator?->name ?? '—' }}</td>

            <td>{{ $widget->sort_order }}</td>
        </tr>
    @empty
        <x-ui.table-empty :colspan="3" message="Sem widgets." />
    @endforelse
</x-ui.table>
