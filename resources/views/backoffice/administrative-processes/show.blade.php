<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Processo administrativo"
            :title="$process->process_number"
            :description="$process->application->application_number.' · '.$process->contest?->title"
        >
            <x-slot name="actions">
                <x-mv.badge>{{ $process->status->label() }}</x-mv.badge>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <x-mv.stat-card label="Candidato" :value="$process->application->adhesionRegistration->full_name" />
                <x-mv.stat-card label="Programa" :value="$process->program?->name ?? '—'" />
                <x-mv.stat-card label="Receção" :value="$process->received_at?->format('d/m/Y H:i') ?? '—'" />
                <x-mv.stat-card label="Técnico responsável" :value="$process->assignedTo?->name ?? 'Por atribuir'" />
            </section>

            <x-mv.section title="Operação do processo">
                <div class="grid gap-4 lg:grid-cols-2">
                    <form method="POST" action="{{ route('backoffice.administrative-processes.assign', $process) }}" class="space-y-3">
                        @csrf
                        <label class="text-sm font-semibold text-ink-700" for="assigned_to">Atribuir técnico</label>
                        <div class="flex gap-3">
                            <select id="assigned_to" name="assigned_to" class="mv-input w-full">
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
            </x-mv.section>

            <section class="grid gap-6 lg:grid-cols-2">
                <x-mv.section title="Candidatura e elegibilidade">
                    <div class="flex items-start justify-between gap-4">
                        <a href="{{ route('backoffice.applications.show', $process->application) }}" class="text-sm font-semibold text-mvhab-primary">Ver candidatura</a>
                    </div>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-ink-500">Agregado</dt><dd class="font-semibold text-ink-900">{{ $process->application->household->members->count() }} membro(s)</dd></div>
                        <div><dt class="text-ink-500">Rendimento mensal declarado</dt><dd class="font-semibold text-ink-900">{{ number_format($process->application->household->incomeRecords->sum('monthly_amount'), 2, ',', '.') }} €</dd></div>
                        <div><dt class="text-ink-500">Última verificação</dt><dd class="font-semibold text-ink-900">{{ $process->application->latestEligibilityCheck?->result?->label() ?? 'Sem verificação formal' }}</dd></div>
                    </dl>
                </x-mv.section>

                <x-mv.section title="Condições para pontuação" description="A candidatura só entra no snapshot quando todas as condições estiverem cumpridas.">
                    <div class="flex items-start justify-between gap-4">
                        <x-mv.badge :tone="$scoringReadiness['ready'] ? 'success' : 'warning'">
                            {{ $scoringReadiness['ready'] ? 'Pronta' : 'Bloqueada' }}
                        </x-mv.badge>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach ($scoringReadiness['items'] as $item)
                            <x-mv.check-card
                                :label="$item['label']"
                                :detail="$item['detail']"
                                :passed="$item['passed']"
                            />
                        @endforeach
                    </div>
                </x-mv.section>

            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <x-mv.section title="Ações processuais">
                    <div class="flex items-start justify-between gap-4">
                        <a href="{{ route('backoffice.administrative-processes.timeline', $process) }}" class="text-sm font-semibold text-mvhab-primary">Cronologia</a>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('backoffice.application-reviews.create', $process) }}" class="mv-button-secondary">Nova análise</a>
                        <a href="{{ route('backoffice.correction-requests.create', $process) }}" class="mv-button-secondary">Pedir aperfeiçoamento</a>
                        <a href="{{ route('backoffice.administrative-decisions.create-admission', $process) }}" class="mv-button-primary">Propor admissão</a>
                        <a href="{{ route('backoffice.administrative-decisions.create-non-admission', $process) }}" class="mv-button-secondary">Propor não admissão</a>
                    </div>
                </x-mv.section>
            </section>

            <x-mv.section title="Decisões administrativas" description="Acompanhe propostas de admissão, não admissão e respetiva aprovação.">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <a href="{{ route('backoffice.administrative-decisions.create-admission', $process) }}" class="mv-button-secondary">Propor admissão</a>
                </div>

                <div class="mt-4 divide-y divide-ink-100">
                    @forelse ($process->decisions as $decision)
                        <div class="flex flex-wrap items-center justify-between gap-4 py-4 text-sm">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $decision->decision_type->label() }}</p>
                                <p class="mt-1 text-ink-600">{{ $decision->decision_result->label() }} · {{ $decision->status->label() }}</p>
                                <p class="mt-1 text-xs text-ink-500">
                                    Registada por {{ $decision->decidedBy?->name ?? '—' }}
                                    em {{ $decision->decided_at?->format('d/m/Y H:i') ?? '—' }}
                                    @if ($decision->approved_at)
                                        · aprovada em {{ $decision->approved_at->format('d/m/Y H:i') }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                @if ($decision->status !== \App\Enums\AdministrativeDecisionStatus::Approved)
                                    <x-mv.badge tone="warning">Aprovação pendente</x-mv.badge>
                                @endif

                                @can('view', $decision)
                                    <a href="{{ route('backoffice.administrative-decisions.show', $decision) }}" class="mv-button-secondary">Abrir decisão</a>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-ink-500">Ainda não existem decisões administrativas registadas para este processo.</p>
                    @endforelse
                </div>
            </x-mv.section>

            <section class="grid gap-6 lg:grid-cols-2">
                <x-mv.section title="Pedidos de aperfeiçoamento">
                    <div class="mt-4 divide-y divide-ink-100">
                        @forelse ($process->correctionRequests as $request)
                            <div class="py-4 text-sm">
                                <a href="{{ route('backoffice.correction-requests.show', $request) }}" class="font-semibold text-mvhab-primary">{{ $request->request_number }}</a>
                                <p class="mt-1 text-ink-700">{{ $request->subject }}</p>
                                <p class="mt-1 text-xs text-ink-500">{{ $request->status->label() }} · prazo {{ $request->response_deadline_at?->format('d/m/Y H:i') ?? '—' }}</p>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-ink-500">Sem pedidos registados.</p>
                        @endforelse
                    </div>
                </x-mv.section>

                <x-mv.section title="Notas internas">
                    <form method="POST" action="{{ route('backoffice.administrative-notes.store', $process) }}" class="mt-4 space-y-3">
                        @csrf
                        <textarea name="body" rows="3" class="mv-input w-full" placeholder="Nota interna"></textarea>
                        <button class="mv-button-primary">Registar nota</button>
                    </form>
                    <div class="mt-4 divide-y divide-ink-100">
                        @foreach ($process->notes as $note)
                            <p class="py-3 text-sm text-ink-700">{{ $note->body }}</p>
                        @endforeach
                    </div>
                </x-mv.section>
            </section>

            <x-mv.section title="Tarefas administrativas">
                <form method="POST" action="{{ route('backoffice.administrative-tasks.store', $process) }}" class="mt-4 grid gap-3 lg:grid-cols-4">
                    @csrf
                    <input name="title" class="mv-input" placeholder="Título">
                    <select name="priority" class="mv-input"><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select>
                    <input type="datetime-local" name="due_at" class="mv-input">
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
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
