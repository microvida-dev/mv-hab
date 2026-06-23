<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Visitas</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">As minhas visitas</h1>
                <p class="mt-1 text-sm text-ink-500">Acompanhe pedidos, confirmações e reagendamentos.</p>
            </div>
            <a href="{{ route('candidate.visits.create') }}" class="mv-button-primary">Agendar visita</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 md:grid-cols-4">
                @foreach ($calendar['indicators'] as $label => $value)
                    <div class="mv-surface p-5">
                        <p class="text-xs font-semibold uppercase text-ink-500">{{ str_replace('_', ' ', $label) }}</p>
                        <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $value }}</p>
                    </div>
                @endforeach
            </section>

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Número</th>
                                <th class="px-5 py-3">Data</th>
                                <th class="px-5 py-3">Contexto</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($visits as $visit)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $visit->visit_number }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $visit->starts_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-5 py-4 text-ink-700">
                                        {{ $visit->housingUnit?->title ?? $visit->contest?->title ?? $visit->application?->application_number ?? 'Contexto processual' }}
                                    </td>
                                    <td class="px-5 py-4"><span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $visit->status->label() }}</span></td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('candidate.visits.show', $visit) }}" class="font-semibold text-civic-700">Consultar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-ink-500">Ainda não existem visitas registadas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{ $visits->links() }}
        </div>
    </div>
</x-app-layout>
