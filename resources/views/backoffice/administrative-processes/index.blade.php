<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Backoffice</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Processos administrativos</h1>
                <p class="mt-1 text-sm text-ink-500">Receção, triagem, aperfeiçoamento e decisão de admissão para classificação.</p>
            </div>
            <a href="{{ route('backoffice.application-intake.index') }}" class="mv-button-primary">Receção de candidaturas</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <form method="GET" class="grid gap-4 border-y border-ink-100 py-5 sm:grid-cols-2 lg:grid-cols-6">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Processo ou candidatura" class="rounded-2xl border-ink-300 text-sm">
                <select name="status" class="rounded-2xl border-ink-300 text-sm">
                    <option value="">Todos os estados</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="contest_id" class="rounded-2xl border-ink-300 text-sm">
                    <option value="">Todos os concursos</option>
                    @foreach ($contests as $contest)
                        <option value="{{ $contest->id }}" @selected((string) request('contest_id') === (string) $contest->id)>{{ $contest->title }}</option>
                    @endforeach
                </select>
                <select name="program_id" class="rounded-2xl border-ink-300 text-sm">
                    <option value="">Todos os programas</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected((string) request('program_id') === (string) $program->id)>{{ $program->name }}</option>
                    @endforeach
                </select>
                <select name="assigned_to" class="rounded-2xl border-ink-300 text-sm">
                    <option value="">Todos os técnicos</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                <button class="mv-button-primary">Filtrar</button>
            </form>

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Processo</th>
                                <th class="px-5 py-3">Candidatura</th>
                                <th class="px-5 py-3">Candidato</th>
                                <th class="px-5 py-3">Concurso</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3">Técnico</th>
                                <th class="px-5 py-3">Prazo ativo</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($processes as $process)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $process->process_number }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $process->application->application_number }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $process->candidate->name }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-ink-900">{{ $process->contest?->title ?? '—' }}</p>
                                        <p class="text-xs text-ink-500">{{ $process->program?->name ?? '—' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-ink-700">{{ $process->status->label() }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $process->assignedTo?->name ?? 'Por atribuir' }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $process->currentCorrectionRequest?->response_deadline_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-5 py-4 text-right"><a href="{{ route('backoffice.administrative-processes.show', $process) }}" class="font-semibold text-mvhab-primary">Consultar</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-5 py-8 text-center text-ink-500">Não existem processos administrativos para os filtros selecionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{ $processes->links() }}
        </div>
    </div>
</x-app-layout>
