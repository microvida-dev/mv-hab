<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><p class="text-sm font-semibold text-civic-700">{{ $ruleSet->name }}</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Critérios</h1></div>
            @can('create', [\App\Models\ScoringCriterion::class, $ruleSet])
                <a href="{{ route('backoffice.scoring.criteria.create', $ruleSet) }}" class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white hover:bg-civic-800">Novo critério</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-md border border-ink-100 bg-white">
            <table class="min-w-full divide-y divide-ink-100 text-sm">
                <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Código</th><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Pontos</th><th class="px-4 py-3">Manual</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                @forelse ($criteria as $criterion)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-ink-700">{{ $criterion->code }}</td>
                        <td class="px-4 py-3 font-semibold text-ink-900">{{ $criterion->name }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $criterion->calculation_type->label() }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $criterion->points ?? '0.00' }} / {{ $criterion->max_points ?? '-' }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $criterion->requires_manual_review ? 'Sim' : 'Não' }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $criterion->is_active ? 'Ativo' : 'Inativo' }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('backoffice.scoring.rules.index', $criterion) }}" class="font-semibold text-civic-700">Regras</a> <span class="text-ink-300">/</span> <a href="{{ route('backoffice.scoring.criteria.edit', $criterion) }}" class="font-semibold text-civic-700">Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-ink-500">Sem critérios configurados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $criteria->links() }}</div>
    </div></div>
</x-app-layout>
