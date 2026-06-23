<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Acesso a dados sensíveis</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface overflow-hidden">
            <table class="mv-table"><thead><tr><th>Data</th><th>Ação</th><th>Utilizador</th><th>Titular</th><th>Sensibilidade</th><th>Justificação</th></tr></thead><tbody>@foreach ($logs as $log)<tr><td>{{ $log->accessed_at?->format('d/m/Y H:i:s') }}</td><td>{{ $log->action }}</td><td>{{ $log->user?->name ?? '—' }}</td><td>{{ $log->subjectUser?->name ?? '—' }}</td><td>{{ $log->sensitivity_level }}</td><td>{{ $log->justification }}</td></tr>@endforeach</tbody></table>
            <div class="p-4">{{ $logs->links() }}</div>
        </section>
    </div></div>
</x-app-layout>
