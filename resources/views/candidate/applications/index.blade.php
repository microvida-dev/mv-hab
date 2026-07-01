<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Candidaturas</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">As minhas candidaturas</h1>
                <p class="mt-1 text-sm text-ink-500">Consulte rascunhos, submissões e comprovativos.</p>
            </div>
            <a href="{{ route('public.contests.index') }}" class="mv-button-primary">
                <x-ui-icon name="plus" class="h-4 w-4" />
                Ver concursos
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if ($applications->isEmpty())
                <section class="mv-surface p-6">
                    <h2 class="text-xl font-semibold text-ink-900">Ainda não iniciou nenhuma candidatura.</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">Consulte os concursos disponíveis para iniciar uma candidatura.</p>
                    <a href="{{ route('public.contests.index') }}" class="mv-button-primary mt-5">Ver concursos disponíveis</a>
                </section>
            @else
                <section class="mv-surface overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-ink-100 text-sm">
                            <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                                <tr>
                                    <th class="px-5 py-3">Número</th>
                                    <th class="px-5 py-3">Concurso</th>
                                    <th class="px-5 py-3">Estado</th>
                                    <th class="px-5 py-3">Criada</th>
                                    <th class="px-5 py-3">Submetida</th>
                                    <th class="px-5 py-3 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100 bg-mvhab-card">
                                @foreach ($applications as $application)
                                    <tr>
                                        <td class="px-5 py-4 font-semibold text-ink-900">{{ $application->application_number ?? 'Rascunho' }}</td>
                                        <td class="px-5 py-4">
                                            <p class="font-semibold text-ink-900">{{ $application->contest->title }}</p>
                                            <p class="mt-1 text-xs text-ink-500">{{ $application->program->name }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="rounded-2xl bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $application->status->label() }}</span>
                                        </td>
                                        <td class="px-5 py-4 text-ink-600">{{ $application->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-5 py-4 text-ink-600">{{ $application->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td class="px-5 py-4 text-right">
                                            <a href="{{ route('candidate.applications.show', $application) }}" class="font-semibold text-mvhab-primary hover:text-mvhab-primary">Consultar</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                {{ $applications->links() }}
            @endif
        </div>
    </div>
</x-app-layout>
