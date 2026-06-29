<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Visitas</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">As minhas visitas</h1>
                <p class="mt-1 text-sm text-ink-500">Acompanhe pedidos, confirmações e reagendamentos.</p>
            </div>

            <a href="{{ route('candidate.visits.create') }}" class="mv-button-primary">
                Agendar visita
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 md:grid-cols-4">
                @foreach ($indicators as $label => $value)
                    <x-ui.card padding="p-5">
                        <p class="text-xs font-semibold uppercase text-ink-500">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $value }}</p>
                    </x-ui.card>
                @endforeach
            </section>

            <x-ui.table :headers="['Número', 'Data', 'Contexto', 'Estado', '']">
                @forelse ($visits as $visit)
                    <tr>
                        <td class="font-semibold text-ink-900">
                            {{ $visit->visit_number }}
                        </td>

                        <td>
                            {{ $visit->starts_at?->format('d/m/Y H:i') ?? '—' }}
                        </td>

                        <td>
                            {{ $visit->housingUnit?->title ?? $visit->contest?->title ?? $visit->application?->application_number ?? 'Contexto processual' }}
                        </td>

                        <td>
                            <x-ui.status-badge
                                status="neutral"
                                :label="$visit->status?->label() ?? 'Estado por confirmar'"
                            />
                        </td>

                        <x-ui.table-actions>
                            <a href="{{ route('candidate.visits.show', $visit) }}" class="font-semibold text-mvhab-primary">
                                Consultar
                            </a>
                        </x-ui.table-actions>
                    </tr>
                @empty
                    <x-ui.table-empty :colspan="5" message="Ainda não existem visitas registadas." />
                @endforelse
            </x-ui.table>

            {{ $visits->links() }}
        </div>
    </div>
</x-app-layout>
