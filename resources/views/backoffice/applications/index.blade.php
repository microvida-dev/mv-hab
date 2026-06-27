<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Backoffice</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Candidaturas formais</h1>
            <p class="mt-1 text-sm text-ink-500">Consulta inicial dos processos criados e submetidos.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-4 border-y border-ink-100 py-5 sm:grid-cols-2 lg:grid-cols-5">
                <input type="search" name="number" value="{{ request('number') }}" placeholder="Número" class="rounded-md border-ink-300 text-sm">
                <select name="status" class="rounded-md border-ink-300 text-sm">
                    <option value="">Todos os estados</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="contest_id" class="rounded-md border-ink-300 text-sm">
                    <option value="">Todos os concursos</option>
                    @foreach ($contests as $contest)
                        <option value="{{ $contest->id }}" @selected((string) request('contest_id') === (string) $contest->id)>{{ $contest->title }}</option>
                    @endforeach
                </select>
                <select name="program_id" class="rounded-md border-ink-300 text-sm">
                    <option value="">Todos os programas</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected((string) request('program_id') === (string) $program->id)>{{ $program->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="mv-button-primary">Filtrar</button>
            </form>

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Número</th>
                                <th class="px-5 py-3">Candidato</th>
                                <th class="px-5 py-3">Concurso</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3">Submissão</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($applications as $application)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $application->application_number ?? 'Rascunho' }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $application->user->name }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-ink-900">{{ $application->contest->title }}</p>
                                        <p class="text-xs text-ink-500">{{ $application->program->name }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-ink-700">{{ $application->status->label() }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $application->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('backoffice.cases.applications.show', $application) }}" class="font-semibold text-civic-700">Abrir processo</a>
                                        <a href="{{ route('backoffice.applications.show', $application) }}" class="ml-3 font-semibold text-ink-500">Detalhe</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-ink-500">Não existem candidaturas para os filtros selecionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{ $applications->links() }}
        </div>
    </div>
</x-app-layout>
