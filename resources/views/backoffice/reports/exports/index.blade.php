<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-ink-900">Exportações</h1>
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
