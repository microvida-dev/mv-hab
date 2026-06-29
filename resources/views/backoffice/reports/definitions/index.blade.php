<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-ink-900">
                Definições de relatório
            </h1>

            @can('create', \App\Models\ReportDefinition::class)
                <a class="mv-button-primary"
                   href="{{ route('backoffice.reports.definitions.create') }}">
                    Nova definição
                </a>
            @endcan
        </div>
    </x-slot>

    <x-ui.table :headers="['Nome', 'Tipo', 'Sensibilidade', 'Estado']">
        @forelse ($reports as $report)
            <tr>
                <td>
                    <a
                        href="{{ route('backoffice.reports.definitions.show', $report) }}"
                        class="font-semibold text-mvhab-primary"
                    >
                        {{ $report->name }}
                    </a>
                </td>

                <td>{{ $report->report_type->label() }}</td>

                <td>{{ $report->sensitivity_level->label() }}</td>

                <td>{{ $report->is_active ? 'Ativo' : 'Inativo' }}</td>
            </tr>
        @empty
            <x-ui.table-empty
                :colspan="4"
                message="Sem relatórios configurados."
            />
        @endforelse
    </x-ui.table>

    <div class="mt-6">
        {{ $reports->links() }}
    </div>
</x-app-layout>
