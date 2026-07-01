<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><p class="text-sm font-semibold text-mvhab-primary">Conjunto de regras</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $ruleSet->name }}</h1><p class="mt-1 text-sm text-ink-500">{{ $ruleSet->contest?->title ?? $ruleSet->program?->name }}</p></div>
            <span class="rounded-2xl bg-ink-100 px-3 py-1 text-sm font-semibold text-ink-700">{{ $ruleSet->status->label() }}</span>
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <section class="grid gap-4 sm:grid-cols-3">
            <div class="mv-surface p-5"><p class="text-sm text-ink-500">Critérios</p><p class="mt-2 text-2xl font-semibold">{{ $ruleSet->criteria->count() }}</p></div>
            <div class="mv-surface p-5"><p class="text-sm text-ink-500">Verificações</p><p class="mt-2 text-2xl font-semibold">{{ $ruleSet->checks_count }}</p></div>
            <div class="mv-surface p-5"><p class="text-sm text-ink-500">Vigência</p><p class="mt-2 text-sm font-semibold">{{ $ruleSet->starts_at?->format('d/m/Y') ?? 'Sem início' }} – {{ $ruleSet->ends_at?->format('d/m/Y') ?? 'Sem fim' }}</p></div>
        </section>
        <section class="mv-surface p-6">
            <p class="text-sm leading-6 text-ink-600">{{ $ruleSet->description ?: 'Sem descrição.' }}</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('backoffice.eligibility.criteria.index', $ruleSet) }}" class="mv-button-primary">Gerir critérios</a>
                @can('update', $ruleSet)<a href="{{ route('backoffice.eligibility.rule-sets.edit', $ruleSet) }}" class="mv-button-secondary">Editar</a>@endcan
                @can('activate', $ruleSet)
                    @if ($ruleSet->status !== \App\Enums\EligibilityRuleSetStatus::Active)
                        <form method="POST" action="{{ route('backoffice.eligibility.rule-sets.activate', $ruleSet) }}">@csrf<button class="mv-button-secondary">Ativar</button></form>
                    @endif
                @endcan
                @can('archive', $ruleSet)
                    @if ($ruleSet->status !== \App\Enums\EligibilityRuleSetStatus::Archived)
                        <form method="POST" action="{{ route('backoffice.eligibility.rule-sets.archive', $ruleSet) }}">@csrf<button class="mv-button-secondary">Arquivar</button></form>
                    @endif
                @endcan
                @can('duplicate', $ruleSet)<form method="POST" action="{{ route('backoffice.eligibility.rule-sets.duplicate', $ruleSet) }}">@csrf<button class="mv-button-secondary">Duplicar</button></form>@endcan
            </div>
        </section>
    </div></div>
</x-app-layout>
