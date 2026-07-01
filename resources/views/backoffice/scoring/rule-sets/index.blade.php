<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Classificação</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Matrizes de classificação</h1>
            </div>
            @can('create', \App\Models\ScoringRuleSet::class)
                <a href="{{ route('backoffice.scoring.rule-sets.create') }}" class="mv-button-primary">Nova matriz</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Contexto</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Critérios</th>
                            <th class="px-4 py-3">Execuções</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($ruleSets as $ruleSet)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ink-900">{{ $ruleSet->name }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $ruleSet->contest?->title ?? $ruleSet->program?->name ?? 'Sem contexto' }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $ruleSet->status->label() }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $ruleSet->criteria_count }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $ruleSet->runs_count }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('backoffice.scoring.rule-sets.show', $ruleSet) }}" class="font-semibold text-mvhab-primary hover:text-mvhab-primary">Abrir</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-ink-500">Sem matrizes configuradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $ruleSets->links() }}</div>
        </div>
    </div>
</x-app-layout>
