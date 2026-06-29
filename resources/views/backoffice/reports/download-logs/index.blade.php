<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-ink-900">Downloads de exportações</h1>
    </x-slot>

    <x-ui.table :headers="['Data', 'Utilizador', 'Relatório', 'Ficheiro', 'IP']">
        @forelse ($logs as $log)
            <tr>
                <td>{{ $log->downloaded_at->format('d/m/Y H:i:s') }}</td>
                <td>{{ $log->user->name }}</td>
                <td>{{ $log->export->run->definition->name }}</td>
                <td>{{ $log->export->file_name }}</td>
                <td>{{ $log->ip_address }}</td>
            </tr>
        @empty
            <x-ui.table-empty :colspan="5" message="Sem downloads registados." />
        @endforelse
    </x-ui.table>

    <div class="mt-6">{{ $logs->links() }}</div>
</x-app-layout>
