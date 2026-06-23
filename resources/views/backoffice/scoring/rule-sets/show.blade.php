<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Matriz de classificação</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $ruleSet->name }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $ruleSet->contest?->title ?? $ruleSet->program?->name ?? 'Sem contexto' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('update', $ruleSet)
                    <a href="{{ route('backoffice.scoring.rule-sets.edit', $ruleSet) }}" class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold text-ink-700 hover:bg-ink-50">Editar</a>
                @endcan
                @can('activate', $ruleSet)
                    <form method="POST" action="{{ route('backoffice.scoring.rule-sets.activate', $ruleSet) }}">@csrf<button class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold text-ink-700 hover:bg-ink-50">Ativar</button></form>
                @endcan
                @can('archive', $ruleSet)
                    <form method="POST" action="{{ route('backoffice.scoring.rule-sets.archive', $ruleSet) }}">@csrf<button class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold text-ink-700 hover:bg-ink-50">Arquivar</button></form>
                @endcan
                @can('duplicate', $ruleSet)
                    <form method="POST" action="{{ route('backoffice.scoring.rule-sets.duplicate', $ruleSet) }}">@csrf<button class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold text-ink-700 hover:bg-ink-50">Duplicar</button></form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="rounded-md border border-ink-100 bg-white p-6 lg:col-span-2">
                <dl class="grid gap-5 md:grid-cols-2">
                    <div><dt class="text-sm text-ink-500">Estado</dt><dd class="mt-1 font-semibold text-ink-900">{{ $ruleSet->status->label() }}</dd></div>
                    <div><dt class="text-sm text-ink-500">Período</dt><dd class="mt-1 font-semibold text-ink-900">{{ $ruleSet->starts_at?->format('d/m/Y H:i') ?? 'Sem início' }} a {{ $ruleSet->ends_at?->format('d/m/Y H:i') ?? 'Sem fim' }}</dd></div>
                    <div><dt class="text-sm text-ink-500">Critérios</dt><dd class="mt-1 font-semibold text-ink-900">{{ $ruleSet->criteria_count }}</dd></div>
                    <div><dt class="text-sm text-ink-500">Regras de desempate</dt><dd class="mt-1 font-semibold text-ink-900">{{ $ruleSet->tie_breaker_rules_count }}</dd></div>
                </dl>
                <p class="mt-6 text-sm leading-6 text-ink-600">{{ $ruleSet->description ?: 'Sem descrição.' }}</p>
            </div>

            <div class="space-y-3 rounded-md border border-ink-100 bg-white p-6">
                <a href="{{ route('backoffice.scoring.criteria.index', $ruleSet) }}" class="block rounded-md border border-ink-200 px-4 py-3 text-sm font-semibold text-ink-800 hover:bg-ink-50">Gerir critérios</a>
                <a href="{{ route('backoffice.scoring.tie-breakers.index', $ruleSet) }}" class="block rounded-md border border-ink-200 px-4 py-3 text-sm font-semibold text-ink-800 hover:bg-ink-50">Gerir desempates</a>
                <a href="{{ route('backoffice.scoring.runs.create', ['scoring_rule_set_id' => $ruleSet->id, 'program_id' => $ruleSet->program_id, 'contest_id' => $ruleSet->contest_id]) }}" class="block rounded-md bg-civic-700 px-4 py-3 text-sm font-semibold text-white hover:bg-civic-800">Executar classificação</a>
            </div>
        </div>
    </div>
</x-app-layout>
