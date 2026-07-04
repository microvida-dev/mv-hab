<x-app-layout>
    @php
        $title = match ($scope) {
            'my' => 'As minhas tarefas',
            'team' => 'Tarefas da equipa',
            'overdue' => 'Tarefas vencidas',
            default => 'Tarefas operacionais',
        };
    @endphp

    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Caixa de trabalho municipal"
            :title="$title"
            description="Filtre e acompanhe tarefas operacionais por estado, prioridade, tipo e prazo."
        >
            <x-slot name="actions">
                <a class="mv-button-secondary" href="{{ route('backoffice.work-tasks.my') }}">Minhas</a>
                <a class="mv-button-secondary" href="{{ route('backoffice.work-tasks.team') }}">Equipa</a>
                <a class="mv-button-secondary" href="{{ route('backoffice.work-tasks.overdue') }}">Vencidas</a>
                <a class="mv-button-primary" href="{{ route('backoffice.work-tasks.dashboard') }}">Painel</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <x-mv.alert tone="success">{{ session('success') }}</x-mv.alert>
            @endif

            <form method="GET">
                <x-mv.section title="Filtros">
                    <div class="grid gap-4 md:grid-cols-5">
                        <label class="text-sm font-semibold text-ink-700">
                            Estado
                            <select name="status" class="mv-input mt-1 w-full">
                                <option value="">Todos</option>
                                @foreach ([\App\Models\WorkTask::STATUS_PENDING, \App\Models\WorkTask::STATUS_ASSIGNED, \App\Models\WorkTask::STATUS_IN_ANALYSIS, \App\Models\WorkTask::STATUS_WAITING_CANDIDATE, \App\Models\WorkTask::STATUS_WAITING_INTERNAL, \App\Models\WorkTask::STATUS_WAITING_EXTERNAL, \App\Models\WorkTask::STATUS_COMPLETED, \App\Models\WorkTask::STATUS_CANCELLED, \App\Models\WorkTask::STATUS_OVERDUE] as $status)
                                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ \App\Models\WorkTask::statusLabel($status) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm font-semibold text-ink-700">
                            Prioridade
                            <select name="priority" class="mv-input mt-1 w-full">
                                <option value="">Todas</option>
                                @foreach ([\App\Models\WorkTask::PRIORITY_LOW => 'Baixa', \App\Models\WorkTask::PRIORITY_NORMAL => 'Normal', \App\Models\WorkTask::PRIORITY_HIGH => 'Alta', \App\Models\WorkTask::PRIORITY_URGENT => 'Urgente'] as $priority => $label)
                                    <option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm font-semibold text-ink-700">
                            Tipo
                            <select name="type" class="mv-input mt-1 w-full">
                                <option value="">Todos</option>
                                @foreach ([\App\Models\WorkTask::TYPE_DOCUMENT_REVIEW, \App\Models\WorkTask::TYPE_ELIGIBILITY_REVIEW, \App\Models\WorkTask::TYPE_SCORING_REVIEW, \App\Models\WorkTask::TYPE_COMPLAINT_REVIEW, \App\Models\WorkTask::TYPE_HEARING_REVIEW, \App\Models\WorkTask::TYPE_CONTRACT_REVIEW, \App\Models\WorkTask::TYPE_RENT_REVIEW, \App\Models\WorkTask::TYPE_PAYMENT_REVIEW, \App\Models\WorkTask::TYPE_MAINTENANCE_TRIAGE, \App\Models\WorkTask::TYPE_INSPECTION_SCHEDULE, \App\Models\WorkTask::TYPE_VISIT_SCHEDULE, \App\Models\WorkTask::TYPE_SUPPORT_TICKET, \App\Models\WorkTask::TYPE_RGPD_REQUEST, \App\Models\WorkTask::TYPE_AUDIT_REVIEW] as $type)
                                    <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ \App\Models\WorkTask::typeLabel($type) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm font-semibold text-ink-700">
                            Prazo
                            <select name="due" class="mv-input mt-1 w-full">
                                <option value="">Todos</option>
                                <option value="soon" @selected(($filters['due'] ?? '') === 'soon')>A vencer</option>
                                <option value="overdue" @selected(($filters['due'] ?? '') === 'overdue')>Vencidas</option>
                            </select>
                        </label>
                        <div class="flex items-end">
                            <button class="mv-button-primary w-full">Filtrar</button>
                        </div>
                    </div>
                </x-mv.section>
            </form>

            <x-mv.section padding="p-0" class="overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr>
                            <th class="px-4 py-3">Tarefa</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Prioridade</th>
                            <th class="px-4 py-3">Equipa</th>
                            <th class="px-4 py-3">Responsável</th>
                            <th class="px-4 py-3">Prazo</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($tasks as $task)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ink-900">{{ $task->task_number }}</td>
                                <td class="px-4 py-3 text-ink-700">{{ \App\Models\WorkTask::typeLabel($task->type) }}</td>
                                <td class="px-4 py-3"><x-mv.badge>{{ \App\Models\WorkTask::statusLabel($task->status) }}</x-mv.badge></td>
                                <td class="px-4 py-3"><x-mv.badge>{{ app(\App\Services\UX\MunicipalLanguageService::class)->priorityLabel((string) $task->priority) }}</x-mv.badge></td>
                                <td class="px-4 py-3 text-ink-600">{{ $task->municipalTeam?->name ?? 'Fila geral' }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $task->assignedUser?->name ?? 'Por atribuir' }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $task->due_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right"><a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.work-tasks.show', $task) }}">Abrir</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-ink-500">Sem tarefas para os filtros selecionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-mv.section>

            {{ $tasks->links() }}
        </div>
    </div>
</x-app-layout>
