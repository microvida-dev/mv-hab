<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Rastreabilidade</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Histórico de elegibilidade</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="mv-table">
                        <thead><tr><th>Contexto</th><th>Tipo</th><th>Resultado</th><th>Data</th><th></th></tr></thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($checks as $check)
                                <tr>
                                    <td>{{ $check->contest?->title ?? $check->program?->name ?? 'Sem regras aplicáveis' }}</td>
                                    <td>{{ $check->check_type->label() }}</td>
                                    <td class="font-semibold">{{ $check->result?->label() ?? 'Em preparação' }}</td>
                                    <td>{{ $check->executed_at?->format('d/m/Y H:i') }}</td>
                                    <td class="text-right"><a href="{{ route('candidate.eligibility.show', $check) }}" class="font-semibold text-civic-700">Consultar</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-ink-500">Ainda não realizou verificações.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 px-4 py-3">{{ $checks->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
