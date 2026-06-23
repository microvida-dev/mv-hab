<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Sorteios</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Sorteios auditáveis</h1>
            </div>
            <a href="{{ route('backoffice.lottery-draws.create') }}" class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Novo sorteio</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <div class="overflow-hidden rounded-md border border-ink-100 bg-white">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr><th class="px-4 py-3">Concurso</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Hash</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse($lotteryDraws as $draw)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $draw->contest?->title ?? 'Sem concurso' }}</td>
                                <td class="px-4 py-3">{{ $draw->draw_type?->label() ?? 'Geral' }}</td>
                                <td class="px-4 py-3">{{ $draw->status->label() }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $draw->result_hash ?? $draw->participants_hash ?? 'Por gerar' }}</td>
                                <td class="px-4 py-3 text-right"><a class="font-semibold text-civic-700" href="{{ route('backoffice.lottery-draws.show', $draw) }}">Abrir</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-ink-500">Sem sorteios auditáveis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $lotteryDraws->links() }}
        </div>
    </div>
</x-app-layout>
