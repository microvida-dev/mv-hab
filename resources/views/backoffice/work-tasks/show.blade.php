<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">{{ $task->task_number }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ \App\Models\WorkTask::typeLabel($task->type) }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ \App\Models\WorkTask::statusLabel($task->status) }} · {{ app(\App\Services\UX\MunicipalLanguageService::class)->priorityLabel((string) $task->priority) }}</p>
            </div>
            <a class="mv-button-secondary" href="{{ route('backoffice.work-tasks.index') }}">Voltar</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
            <div class="space-y-6">
                @if (session('success'))
                    <div class="rounded-2xl border border-mvhab-support/40 bg-mvhab-surface px-4 py-3 text-sm font-semibold text-mvhab-primary">{{ session('success') }}</div>
                @endif

                <section class="mv-surface p-5">
                    <h2 class="text-lg font-semibold text-ink-900">Resumo</h2>
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="font-semibold text-ink-500">Equipa</dt><dd class="mt-1 text-ink-900">{{ $task->municipalTeam?->name ?? 'Fila geral' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Responsável</dt><dd class="mt-1 text-ink-900">{{ $task->assignedUser?->name ?? 'Por atribuir' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Prazo</dt><dd class="mt-1 text-ink-900">{{ $task->due_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Origem</dt><dd class="mt-1 text-ink-900">{{ $task->source ?? 'Manual' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Entidade relacionada</dt><dd class="mt-1 text-ink-900">{{ class_basename((string) $task->related_type) ?: '—' }} #{{ $task->related_id ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Criada em</dt><dd class="mt-1 text-ink-900">{{ $task->created_at?->format('d/m/Y H:i') }}</dd></div>
                    </dl>
                </section>

                <section class="mv-surface overflow-hidden">
                    <div class="border-b border-ink-100 px-5 py-4">
                        <h2 class="text-lg font-semibold text-ink-900">Histórico</h2>
                    </div>
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr><th class="px-4 py-3">Evento</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Actor</th><th class="px-4 py-3">Data</th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($task->histories as $history)
                                <tr>
                                    <td class="px-4 py-3"><span class="font-semibold text-ink-900">{{ $history->event_code }}</span><p class="mt-1 text-xs text-ink-500">{{ $history->note }}</p></td>
                                    <td class="px-4 py-3 text-ink-700">{{ $history->from_status ? \App\Models\WorkTask::statusLabel($history->from_status).' → ' : '' }}{{ $history->to_status ? \App\Models\WorkTask::statusLabel($history->to_status) : '—' }}</td>
                                    <td class="px-4 py-3 text-ink-700">{{ $history->actor?->name ?? 'Sistema' }}</td>
                                    <td class="px-4 py-3 text-ink-600">{{ $history->occurred_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-ink-500">Sem histórico.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>

            <aside class="space-y-6">
                @can('claim', $task)
                    <form method="POST" action="{{ route('backoffice.work-tasks.claim', $task) }}" class="mv-surface p-5">
                        @csrf
                        <h2 class="text-base font-semibold text-ink-900">Assumir tarefa</h2>
                        <button class="mv-button-primary mt-4 w-full">Assumir</button>
                    </form>
                @endcan

                @can('reassign', $task)
                    <form method="POST" action="{{ route('backoffice.work-tasks.reassign', $task) }}" class="mv-surface space-y-4 p-5">
                        @csrf
                        <h2 class="text-base font-semibold text-ink-900">Reatribuir</h2>
                        <label class="block text-sm font-semibold text-ink-700">
                            Equipa
                            <select name="municipal_team_id" class="mv-input mt-1 w-full">
                                <option value="">Fila geral</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected($task->municipal_team_id === $team->id)>{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm font-semibold text-ink-700">
                            Responsável
                            <select name="assigned_user_id" class="mv-input mt-1 w-full">
                                <option value="">Por atribuir</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected($task->assigned_user_id === $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm font-semibold text-ink-700">
                            Justificação
                            <textarea name="reason" rows="3" class="mv-input mt-1 w-full" required></textarea>
                        </label>
                        <button class="mv-button-primary w-full">Reatribuir</button>
                    </form>
                @endcan

                @can('updateStatus', $task)
                    <form method="POST" action="{{ route('backoffice.work-tasks.status', $task) }}" class="mv-surface space-y-4 p-5">
                        @csrf
                        <h2 class="text-base font-semibold text-ink-900">Atualizar estado</h2>
                        <label class="block text-sm font-semibold text-ink-700">
                            Estado
                            <select name="status" class="mv-input mt-1 w-full">
                                <option value="{{ \App\Models\WorkTask::STATUS_IN_ANALYSIS }}">Em análise</option>
                                <option value="{{ \App\Models\WorkTask::STATUS_WAITING_CANDIDATE }}">Em espera pelo candidato</option>
                                <option value="{{ \App\Models\WorkTask::STATUS_WAITING_INTERNAL }}">Em espera interna</option>
                                <option value="{{ \App\Models\WorkTask::STATUS_WAITING_EXTERNAL }}">Em espera externa</option>
                            </select>
                        </label>
                        <label class="block text-sm font-semibold text-ink-700">
                            Nota
                            <textarea name="note" rows="3" class="mv-input mt-1 w-full"></textarea>
                        </label>
                        <button class="mv-button-primary w-full">Atualizar</button>
                    </form>
                @endcan

                @can('complete', $task)
                    <form method="POST" action="{{ route('backoffice.work-tasks.status', $task) }}" class="mv-surface space-y-4 p-5">
                        @csrf
                        <input type="hidden" name="status" value="{{ \App\Models\WorkTask::STATUS_COMPLETED }}">
                        <h2 class="text-base font-semibold text-ink-900">Concluir</h2>
                        <label class="block text-sm font-semibold text-ink-700">
                            Resultado
                            <textarea name="outcome_note" rows="3" class="mv-input mt-1 w-full" required></textarea>
                        </label>
                        <button class="mv-button-primary w-full">Concluir</button>
                    </form>
                @endcan

                @can('cancel', $task)
                    <form method="POST" action="{{ route('backoffice.work-tasks.status', $task) }}" class="mv-surface space-y-4 p-5">
                        @csrf
                        <input type="hidden" name="status" value="{{ \App\Models\WorkTask::STATUS_CANCELLED }}">
                        <h2 class="text-base font-semibold text-ink-900">Cancelar</h2>
                        <label class="block text-sm font-semibold text-ink-700">
                            Motivo
                            <textarea name="cancellation_reason" rows="3" class="mv-input mt-1 w-full" required></textarea>
                        </label>
                        <button class="mv-button-secondary w-full">Cancelar tarefa</button>
                    </form>
                @endcan
            </aside>
        </div>
    </div>
</x-app-layout>
