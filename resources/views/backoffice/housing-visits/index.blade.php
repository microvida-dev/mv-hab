<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Backoffice</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Visitas agendadas</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr><th class="px-5 py-3">Número</th><th class="px-5 py-3">Candidato</th><th class="px-5 py-3">Data</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Contexto</th><th class="px-5 py-3 text-right">Ações</th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($visits as $visit)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $visit->visit_number }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $visit->candidate?->name }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $visit->starts_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $visit->status->label() }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $visit->housingUnit?->title ?? $visit->contest?->title ?? $visit->application?->application_number ?? '—' }}</td>
                                    <td class="px-5 py-4 text-right"><a href="{{ route('backoffice.housing-visits.show', $visit) }}" class="font-semibold text-mvhab-primary">Consultar</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-ink-500">Sem visitas agendadas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            {{ $visits->links() }}
        </div>
    </div>
</x-app-layout>
