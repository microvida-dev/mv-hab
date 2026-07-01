<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><p class="text-sm font-semibold text-mvhab-primary">{{ $criterion->name }}</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Regras de pontuação</h1></div>
            @can('create', [\App\Models\ScoringRule::class, $criterion])
                <a href="{{ route('backoffice.scoring.rules.create', $criterion) }}" class="mv-button-primary">Nova regra</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mv-surface overflow-hidden">
            <table class="min-w-full divide-y divide-ink-100 text-sm">
                <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Etiqueta</th><th class="px-4 py-3">Operador</th><th class="px-4 py-3">Pontos</th><th class="px-4 py-3">Peso</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                @forelse ($rules as $rule)
                    <tr><td class="px-4 py-3 font-semibold text-ink-900">{{ $rule->label }}</td><td class="px-4 py-3 text-ink-600">{{ $rule->operator?->label() ?? 'Critério' }}</td><td class="px-4 py-3 text-ink-600">{{ $rule->points }}</td><td class="px-4 py-3 text-ink-600">{{ $rule->weight }}</td><td class="px-4 py-3 text-ink-600">{{ $rule->is_active ? 'Ativa' : 'Inativa' }}</td><td class="px-4 py-3 text-right"><a href="{{ route('backoffice.scoring.rules.edit', $rule) }}" class="font-semibold text-mvhab-primary">Editar</a></td></tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-ink-500">Sem regras configuradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $rules->links() }}</div>
    </div></div>
</x-app-layout>
