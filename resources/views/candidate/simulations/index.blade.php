<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Simulador</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">As minhas simulações</h1>
                <p class="mt-1 text-sm text-ink-500">Histórico de simulações indicativas e conversões para rascunho.</p>
            </div>
            <a href="{{ route('candidate.simulations.create') }}" class="mv-button-primary">Nova simulação</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr><th class="px-5 py-3">Data</th><th class="px-5 py-3">Resultado</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Ações</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100 bg-white">
                        @forelse ($sessions as $session)
                            <tr>
                                <td class="px-5 py-4">{{ $session->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4">{{ $session->result_status?->label() ?? 'A validar' }}</td>
                                <td class="px-5 py-4">{{ $session->status->label() }}</td>
                                <td class="px-5 py-4 text-right"><a class="font-semibold text-civic-700" href="{{ route('candidate.simulations.show', $session) }}">Consultar</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-ink-500">Ainda não existem simulações.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            {{ $sessions->links() }}
        </div>
    </div>
</x-app-layout>
