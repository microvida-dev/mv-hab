<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header title="Exportações" description="Histórico de relatórios e artefactos privados.">
            <x-slot name="actions">
                @can('createTemporal', \App\Models\ReportExport::class)
                    <a class="mv-button-primary" href="{{ route('backoffice.reports.temporal-exports.index') }}">
                        Exportações temporais
                    </a>
                @endcan
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <x-ui.table :headers="['Relatório', 'Formato', 'Âmbito', 'Estado', 'Expira']">
        @forelse ($exports as $export)
            <tr>
                <td>
                    <a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.reports.exports.show', $export) }}">
                        {{ $export->run->definition->name }}
                    </a>
                </td>
                <td>{{ $export->format->label() }}</td>
                <td>{{ $export->scope->label() }}</td>
                <td>{{ $export->status->label() }}</td>
                <td>{{ $export->expires_at?->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <x-ui.table-empty :colspan="5" message="Sem exportações registadas." />
        @endforelse
    </x-ui.table>

    <div class="mt-6">{{ $exports->links() }}</div>
</x-app-layout>
