<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Comunicações transversais</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Centro de comunicações</h1>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('backoffice.communications.logs.index') }}" class="mv-button-secondary">Histórico</a>
                <a href="{{ route('backoffice.communications.templates.index') }}" class="mv-button-primary">Gerir templates</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6" aria-label="Indicadores de comunicação">
                @foreach ([
                    'Comunicações' => $totals['communications'],
                    'Em fila' => $totals['queued'],
                    'Falhadas' => $totals['failed'],
                    'Aguarda configuração' => $totals['pending_configuration'],
                    'Templates' => $totals['templates'],
                    'Documentos' => $totals['documents'],
                ] as $label => $value)
                    <div class="rounded-md border border-ink-100 bg-white p-5">
                        <p class="text-xs font-semibold uppercase text-ink-500">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $value }}</p>
                    </div>
                @endforeach
            </section>

            <section>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-ink-900">Atividade recente</h2>
                    <a href="{{ route('backoffice.communications.event-rules.index') }}" class="text-sm font-semibold text-civic-700">Regras por evento</a>
                </div>
                <div class="overflow-hidden rounded-md border border-ink-100 bg-white">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr><th class="px-4 py-3">Número</th><th class="px-4 py-3">Destinatário</th><th class="px-4 py-3">Evento</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($recent as $communication)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-ink-900">{{ $communication->communication_number }}</td>
                                    <td class="px-4 py-3">{{ $communication->recipient?->name ?? 'Sem destinatário associado' }}</td>
                                    <td class="px-4 py-3">{{ $communication->event_code }}</td>
                                    <td class="px-4 py-3">{{ $communication->status->label() }}</td>
                                    <td class="px-4 py-3 text-right"><a href="{{ route('backoffice.communications.logs.show', $communication) }}" class="font-semibold text-civic-700">Abrir</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-ink-500">Ainda não existem comunicações registadas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
