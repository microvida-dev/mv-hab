<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Backoffice</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Tickets de apoio</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="grid gap-4 md:grid-cols-4">
                @foreach ($indicators as $label => $value)
                    <div class="mv-surface p-5">
                        <p class="text-xs font-semibold uppercase text-ink-500">{{ str_replace('_', ' ', $label) }}</p>
                        <p class="mt-2 text-2xl font-semibold text-ink-900">{{ is_array($value) ? array_sum($value) : $value }}</p>
                    </div>
                @endforeach
            </section>
            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr><th class="px-5 py-3">Número</th><th class="px-5 py-3">Candidato</th><th class="px-5 py-3">Assunto</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Técnico</th><th class="px-5 py-3 text-right">Ações</th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($tickets as $ticket)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $ticket->ticket_number }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $ticket->user?->name }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $ticket->subject }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $ticket->status->label() }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $ticket->assignee?->name ?? '—' }}</td>
                                    <td class="px-5 py-4 text-right"><a href="{{ route('backoffice.support-tickets.show', $ticket) }}" class="font-semibold text-civic-700">Abrir</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-ink-500">Sem tickets registados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            {{ $tickets->links() }}
        </div>
    </div>
</x-app-layout>
