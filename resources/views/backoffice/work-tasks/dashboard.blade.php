<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Produtividade operacional</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Dashboard de tarefas</h1>
            </div>
            <a class="mv-button-secondary" href="{{ route('backoffice.work-tasks.index') }}">Tarefas</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-4">
                <div class="mv-surface p-5"><p class="text-sm font-semibold text-ink-500">Vencidas</p><p class="mt-2 text-3xl font-semibold text-ink-900">{{ $metrics['overdue'] }}</p></div>
                <div class="mv-surface p-5"><p class="text-sm font-semibold text-ink-500">A vencer</p><p class="mt-2 text-3xl font-semibold text-ink-900">{{ $metrics['due_soon'] }}</p></div>
                <div class="mv-surface p-5"><p class="text-sm font-semibold text-ink-500">Concluídas 30 dias</p><p class="mt-2 text-3xl font-semibold text-ink-900">{{ $metrics['completed_last_30_days'] }}</p></div>
                <div class="mv-surface p-5"><p class="text-sm font-semibold text-ink-500">Cumprimento SLA</p><p class="mt-2 text-3xl font-semibold text-ink-900">{{ number_format($metrics['sla_rate'], 2, ',', ' ') }}%</p></div>
            </section>

            <section class="mv-surface overflow-hidden">
                <div class="border-b border-ink-100 px-5 py-4">
                    <h2 class="text-lg font-semibold text-ink-900">Estados</h2>
                </div>
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($metrics['by_status'] as $status => $total)
                            <tr><td class="px-4 py-3 font-semibold text-ink-900">{{ \App\Models\WorkTask::statusLabel($status) }}</td><td class="px-4 py-3 text-right text-ink-700">{{ $total }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="mv-surface overflow-hidden">
                    <div class="border-b border-ink-100 px-5 py-4">
                        <h2 class="text-lg font-semibold text-ink-900">Carga por equipa</h2>
                    </div>
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($metrics['team_load'] as $row)
                                <tr><td class="px-4 py-3 text-ink-900">Equipa #{{ $row->municipal_team_id ?? 'geral' }}</td><td class="px-4 py-3 text-ink-600">{{ \App\Models\WorkTask::statusLabel($row->status) }}</td><td class="px-4 py-3 text-right font-semibold text-ink-900">{{ $row->total }}</td></tr>
                            @empty
                                <tr><td class="px-4 py-8 text-center text-ink-500">Sem dados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mv-surface overflow-hidden">
                    <div class="border-b border-ink-100 px-5 py-4">
                        <h2 class="text-lg font-semibold text-ink-900">Carga por utilizador</h2>
                    </div>
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($metrics['user_load'] as $row)
                                <tr><td class="px-4 py-3 text-ink-900">Utilizador #{{ $row->assigned_user_id ?? 'fila' }}</td><td class="px-4 py-3 text-ink-600">{{ \App\Models\WorkTask::statusLabel($row->status) }}</td><td class="px-4 py-3 text-right font-semibold text-ink-900">{{ $row->total }}</td></tr>
                            @empty
                                <tr><td class="px-4 py-8 text-center text-ink-500">Sem dados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
