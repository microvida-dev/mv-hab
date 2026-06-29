<x-ui.table :headers="['Painel','Tipo','Widgets','Estado']">
    @forelse ($dashboards as $dashboard)
        <tr>
            <td>
                <a class="font-semibold text-mvhab-primary"
                   href="{{ route('backoffice.reports.dashboards.edit',$dashboard) }}">
                    {{ $dashboard->name }}
                </a>
            </td>

            <td>{{ $dashboard->dashboard_type->label() }}</td>

            <td>{{ $dashboard->widgets_count }}</td>

            <td>{{ $dashboard->is_active ? 'Ativo' : 'Inativo' }}</td>
        </tr>
    @empty
        <x-ui.table-empty :colspan="4" message="Sem painéis." />
    @endforelse
</x-ui.table>
