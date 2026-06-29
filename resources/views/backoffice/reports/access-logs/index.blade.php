<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-ink-900">Acessos a relatórios</h1>
    </x-slot>

    <x-ui.table :headers="['Data', 'Utilizador', 'Ação', 'Relatório / painel', 'Âmbito']">
        @forelse ($logs as $log)
            <tr>
                <td>{{ $log->accessed_at->format('d/m/Y H:i:s') }}</td>
                <td>{{ $log->user->name }}</td>
                <td>{{ $log->access_type->label() }}</td>
                <td>{{ $log->definition?->name ?? $log->dashboard?->name ?? '—' }}</td>
                <td>{{ $log->scope?->label() ?? '—' }}</td>
            </tr>
        @empty
            <x-ui.table-empty :colspan="5" message="Sem acessos registados." />
        @endforelse
    </x-ui.table>

    <div class="mt-6">{{ $logs->links() }}</div>
</x-app-layout>
