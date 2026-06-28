<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Processo administrativo</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $process->process_number }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $process->application->application_number }} · {{ $process->contest?->title }}</p>
            </div>
            <span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $process->status->label() }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="mv-surface p-5"><p class="text-sm text-ink-500">Candidato</p><p class="mt-2 font-semibold text-ink-900">{{ $process->application->adhesionRegistration->full_name }}</p></div>
                <div class="mv-surface p-5"><p class="text-sm text-ink-500">Programa</p><p class="mt-2 font-semibold text-ink-900">{{ $process->program?->name ?? '—' }}</p></div>
                <div class="mv-surface p-5"><p class="text-sm text-ink-500">Receção</p><p class="mt-2 font-semibold text-ink-900">{{ $process->received_at?->format('d/m/Y H:i') ?? '—' }}</p></div>
                <div class="mv-surface p-5"><p class="text-sm text-ink-500">Técnico responsável</p><p class="mt-2 font-semibold text-ink-900">{{ $process->assignedTo?->name ?? 'Por atribuir' }}</p></div>
            </section>

            <section class="mv-surface p-6">
                <div class="grid gap-4 lg:grid-cols-2">
                    <form method="POST" action="{{ route('backoffice.administrative-processes.assign', $process) }}" class="space-y-3">
                        @csrf
                        <label class="text-sm font-semibold text-ink-700" for="assigned_to">Atribuir técnico</label>
                        <div class="flex gap-3">
                            <select id="assigned_to" name="assigned_to" class="w-full rounded-md border-ink-300 text-sm">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected($process->assigned_to === $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <button class="mv-button-primary">Atribuir</button>
                        </div>
                    </form>
                    <div class="flex flex-wrap items-end gap-3">
                        <form method="POST" action="{{ route('backoffice.administrative-processes.start-preliminary-review', $process) }}">@csrf<button class="mv-button-secondary">Iniciar triagem</button></form>
                        <form method="POST" action="{{ route('backoffice.administrative-processes.start-document-review', $process) }}">@csrf<button class="mv-button-secondary">Análise documental</button></form>
                        <form method="POST" action="{{ route('backoffice.administrative-processes.start-eligibility-review', $process) }}">@csrf<button class="mv-button-secondary">Análise de requisitos</button></form>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="mv-surface p-6">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="text-lg font-semibold text-ink-900">Candidatura e elegibilidade</h2>
                        <a href="{{ route('backoffice.applications.show', $process->application) }}" class="text-sm font-semibold text-civic-700">Ver candidatura</a>
                    </div>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-ink-500">Agregado</dt><dd class="font-semibold text-ink-900">{{ $process->application->household->members->count() }} membro(s)</dd></div>
                        <div><dt class="text-ink-500">Rendimento mensal declarado</dt><dd class="font-semibold text-ink-900">{{ number_format($process->application->household->incomeRecords->sum('monthly_amount'), 2, ',', '.') }} €</dd></div>
                        <div><dt class="text-ink-500">Última verificação</dt><dd class="font-semibold text-ink-900">{{ $process->application->latestEligibilityCheck?->result?->label() ?? 'Sem verificação formal' }}</dd></div>
                    </dl>
                </div>

                <div class="mv-surface p-6">
                    <div class="flex items-start justify-between gap-4">
                        <h2 class="text-lg font-semibold text-ink-900">Ações processuais</h2>
                        <a href="{{ route('backoffice.administrative-processes.timeline', $process) }}" class="text-sm font-semibold text-civic-700">Cronologia</a>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('backoffice.application-reviews.create', $process) }}" class="mv-button-secondary">Nova análise</a>
                        <a href="{{ route('backoffice.correction-requests.create', $process) }}" class="mv-button-secondary">Pedir aperfeiçoamento</a>
                        <a href="{{ route('backoffice.administrative-decisions.create-admission', $process) }}" class="mv-button-primary">Propor admissão</a>
                        <a href="{{ route('backoffice.administrative-decisions.create-non-admission', $process) }}" class="mv-button-secondary">Propor não admissão</a>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Pedidos de aperfeiçoamento</h2>
                    <div class="mt-4 divide-y divide-ink-100">
                        @forelse ($process->correctionRequests as $request)
                            <div class="py-4 text-sm">
                                <a href="{{ route('backoffice.correction-requests.show', $request) }}" class="font-semibold text-civic-700">{{ $request->request_number }}</a>
                                <p class="mt-1 text-ink-700">{{ $request->subject }}</p>
                                <p class="mt-1 text-xs text-ink-500">{{ $request->status->label() }} · prazo {{ $request->response_deadline_at?->format('d/m/Y H:i') ?? '—' }}</p>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-ink-500">Sem pedidos registados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Notas internas</h2>
                    <form method="POST" action="{{ route('backoffice.administrative-notes.store', $process) }}" class="mt-4 space-y-3">
                        @csrf
                        <textarea name="body" rows="3" class="w-full rounded-md border-ink-300 text-sm" placeholder="Nota interna"></textarea>
                        <button class="mv-button-primary">Registar nota</button>
                    </form>
                    <div class="mt-4 divide-y divide-ink-100">
                        @foreach ($process->notes as $note)
                            <p class="py-3 text-sm text-ink-700">{{ $note->body }}</p>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Tarefas administrativas</h2>
                <form method="POST" action="{{ route('backoffice.administrative-tasks.store', $process) }}" class="mt-4 grid gap-3 lg:grid-cols-4">
                    @csrf
                    <input name="title" class="rounded-md border-ink-300 text-sm" placeholder="Título">
                    <select name="priority" class="rounded-md border-ink-300 text-sm"><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select>
                    <input type="datetime-local" name="due_at" class="rounded-md border-ink-300 text-sm">
                    <button class="mv-button-secondary">Criar tarefa</button>
                </form>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach ($process->tasks as $task)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                            <span class="font-semibold text-ink-900">{{ $task->title }}</span>
                            <span class="text-ink-500">{{ $task->status->label() }} · {{ $task->priority->label() }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
