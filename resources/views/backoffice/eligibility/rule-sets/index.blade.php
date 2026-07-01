<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><p class="text-sm font-semibold text-mvhab-primary">Configuração</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Regras de elegibilidade</h1></div>
            @can('create', \App\Models\EligibilityRuleSet::class)
                <a href="{{ route('backoffice.eligibility.rule-sets.create') }}" class="mv-button-primary"><x-ui-icon name="plus" class="h-4 w-4" />Novo conjunto</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <div class="mv-surface overflow-hidden"><div class="overflow-x-auto"><table class="mv-table">
            <thead><tr><th>Nome</th><th>Contexto</th><th>Estado</th><th>Critérios</th><th>Checks</th><th></th></tr></thead>
            <tbody class="divide-y divide-ink-100">
                @forelse ($ruleSets as $ruleSet)
                    <tr>
                        <td class="font-semibold text-ink-900">{{ $ruleSet->name }}</td>
                        <td>{{ $ruleSet->contest?->title ?? $ruleSet->program?->name }}</td>
                        <td>{{ $ruleSet->status->label() }}</td>
                        <td>{{ $ruleSet->criteria_count }}</td><td>{{ $ruleSet->checks_count }}</td>
                        <td class="text-right"><a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.eligibility.rule-sets.show', $ruleSet) }}">Abrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-ink-500">Sem conjuntos configurados.</td></tr>
                @endforelse
            </tbody>
        </table></div><div class="border-t border-ink-100 px-4 py-3">{{ $ruleSets->links() }}</div></div>
    </div></div>
</x-app-layout>
