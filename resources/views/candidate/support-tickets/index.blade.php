<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Apoio</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Pedidos de apoio</h1>
                <p class="mt-1 text-sm text-ink-500">Acompanhe pedidos e respostas dos serviços municipais.</p>
            </div>
            <a href="{{ route('candidate.support-tickets.create') }}" class="mv-button-primary">Novo pedido</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Número</th>
                                <th class="px-5 py-3">Assunto</th>
                                <th class="px-5 py-3">Categoria</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3">Atualizado</th>
                                <th class="px-5 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($tickets as $ticket)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $ticket->ticket_number }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $ticket->subject }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $ticket->category->label() }}</td>
                                    <td class="px-5 py-4"><span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $ticket->status->label() }}</span></td>
                                    <td class="px-5 py-4 text-ink-600">{{ $ticket->last_message_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-5 py-4 text-right"><a href="{{ route('candidate.support-tickets.show', $ticket) }}" class="font-semibold text-civic-700">Abrir</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-ink-500">Ainda não existem pedidos de apoio.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            {{ $tickets->links() }}
        </div>
    </div>
</x-app-layout>
