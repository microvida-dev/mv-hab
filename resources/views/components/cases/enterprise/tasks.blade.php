@props([
    'tasks' => [],
])

<section id="case-tab-tasks" class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Tarefas associadas" description="Tarefas existentes, com SLA próprio e sem alteração de estado." />
    </div>

    <div class="divide-y divide-ink-100">
        @forelse ($tasks as $task)
            <article class="px-5 py-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-ink-900">{{ $task['label'] }}</p>
                        <p class="mt-1 text-sm text-ink-500">Prazo: {{ $task['due_at']?->format('d/m/Y H:i') ?? 'Sem prazo' }}</p>
                    </div>
                    <div class="flex gap-2">
                        <x-ui.status-badge status="neutral" :label="$task['priority']" />
                        <x-ui.status-badge :label="$task['status']" />
                    </div>
                </div>

                @if ($task['route'])
                    <a href="{{ route($task['route'], $task['parameters'] ?? []) }}" class="mt-3 inline-flex text-sm font-semibold text-mvhab-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-offset-2">
                        Abrir tarefa
                    </a>
                @endif
            </article>
        @empty
            <div class="p-5">
                <x-cases.enterprise.empty-state title="Sem tarefas" description="Não existem tarefas autorizadas associadas a este caso." />
            </div>
        @endforelse
    </div>
</section>
