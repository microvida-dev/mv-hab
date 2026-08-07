<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-civic-700">Perfil regulamentar</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $regulatoryProfile->name }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $regulatoryProfile->code }} · {{ $regulatoryProfile->version }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('updateBackoffice', $regulatoryProfile)
                    @if ($regulatoryProfile->snapshots_count === 0 && $regulatoryProfile->status->value !== 'archived')
                        <a href="{{ route('admin.regulatory-profiles.edit', $regulatoryProfile) }}" class="mv-button-secondary">Editar</a>
                    @endif
                    <a href="{{ route('admin.regulatory-profiles.rent-limits.edit', $regulatoryProfile) }}" class="mv-button-secondary">Tabela de rendas</a>
                    @if ($regulatoryProfile->status->value !== 'active')
                        <form method="POST" action="{{ route('admin.regulatory-profiles.activate', $regulatoryProfile) }}">@csrf<button class="mv-button-primary">Ativar</button></form>
                    @endif
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @if ($errors->any())
                <x-mv.alert tone="danger">{{ $errors->first() }}</x-mv.alert>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <section class="mv-surface p-6">
                    <div class="flex flex-wrap gap-2">
                        <x-mv.badge :tone="$regulatoryProfile->status->value === 'active' ? 'success' : 'warning'">{{ $regulatoryProfile->status->label() }}</x-mv.badge>
                        <x-mv.badge :tone="$regulatoryProfile->configuration_status->value === 'complete' ? 'success' : 'warning'">{{ $regulatoryProfile->configuration_status->label() }}</x-mv.badge>
                        <x-mv.badge>{{ $regulatoryProfile->municipality?->name ?? 'Nacional' }}</x-mv.badge>
                    </div>

                    <h2 class="mt-7 text-lg font-semibold text-ink-900">Base legal</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-600">{{ $regulatoryProfile->legal_basis }}</p>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-ink-50 p-4"><p class="text-xs font-semibold uppercase text-ink-500">Fonte oficial</p><p class="mt-2 text-sm text-ink-800">{{ $regulatoryProfile->official_source ?: 'Por preencher' }}</p></div>
                        <div class="rounded-2xl bg-ink-50 p-4"><p class="text-xs font-semibold uppercase text-ink-500">Referência / versão</p><p class="mt-2 text-sm text-ink-800">{{ $regulatoryProfile->publication_reference ?: '—' }} · {{ $regulatoryProfile->source_version ?: '—' }}</p></div>
                    </div>

                    <h2 class="mt-8 text-lg font-semibold text-ink-900">Prontidão estrutural</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            ['label' => 'Limites de renda', 'ok' => $regulatoryProfile->rent_limits_configured],
                            ['label' => 'Elegibilidade', 'ok' => $regulatoryProfile->eligibility_rules_configured && $ruleSetCounts['eligibility'] > 0],
                            ['label' => 'Tipologia', 'ok' => $regulatoryProfile->typology_rules_configured && $ruleSetCounts['typology'] > 0],
                            ['label' => 'Atribuição', 'ok' => $ruleSetCounts['allocation'] > 0],
                            ['label' => 'Regras de renda', 'ok' => $ruleSetCounts['rent'] > 0],
                            ['label' => 'Termos contratuais', 'ok' => $regulatoryProfile->contract_terms_configured],
                        ] as $check)
                            <div class="flex items-center justify-between rounded-2xl border border-ink-100 p-4">
                                <span class="text-sm font-semibold text-ink-800">{{ $check['label'] }}</span>
                                <x-mv.badge :tone="$check['ok'] ? 'success' : 'warning'">{{ $check['ok'] ? 'Configurado' : 'Pendente' }}</x-mv.badge>
                            </div>
                        @endforeach
                    </div>

                    <h2 class="mt-8 text-lg font-semibold text-ink-900">Atalhos de configuração</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('backoffice.eligibility.rule-sets.index') }}" class="mv-button-secondary">Elegibilidade</a>
                        <a href="{{ route('backoffice.allocation.typology-rules.index') }}" class="mv-button-secondary">Tipologia</a>
                        <a href="{{ route('backoffice.allocation.rule-sets.index') }}" class="mv-button-secondary">Atribuição</a>
                        <a href="{{ route('backoffice.contracts.rent-rule-sets.index') }}" class="mv-button-secondary">Regras de renda</a>
                        <a href="{{ route('admin.regulatory-profiles.rent-limits.edit', $regulatoryProfile) }}" class="mv-button-primary">Limites de renda</a>
                    </div>
                </section>

                <aside class="space-y-4">
                    <section class="mv-surface p-5">
                        <h2 class="font-semibold text-ink-900">Vigência</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-ink-500">Regime</dt><dd class="mt-1 font-semibold text-ink-900">{{ $regulatoryProfile->legal_regime->label() }}</dd></div>
                            <div><dt class="text-ink-500">Desde</dt><dd class="mt-1 font-semibold text-ink-900">{{ $regulatoryProfile->effective_from->format('d/m/Y') }}</dd></div>
                            <div><dt class="text-ink-500">Até</dt><dd class="mt-1 font-semibold text-ink-900">{{ $regulatoryProfile->effective_until?->format('d/m/Y') ?? 'Sem termo' }}</dd></div>
                            <div><dt class="text-ink-500">Perfil pai</dt><dd class="mt-1 font-semibold text-ink-900">{{ $regulatoryProfile->parentProfile?->name ?? '—' }}</dd></div>
                            <div><dt class="text-ink-500">Snapshots</dt><dd class="mt-1 font-semibold text-ink-900">{{ $regulatoryProfile->snapshots_count }}</dd></div>
                        </dl>
                    </section>

                    @can('archiveBackoffice', $regulatoryProfile)
                        @if ($regulatoryProfile->status->value !== 'archived' && $regulatoryProfile->snapshots_count === 0)
                            <section class="mv-surface p-5">
                                <h2 class="font-semibold text-ink-900">Ciclo de vida</h2>
                                <p class="mt-2 text-sm text-ink-500">Arquivar retira o perfil de novas associações.</p>
                                <form method="POST" action="{{ route('admin.regulatory-profiles.archive', $regulatoryProfile) }}" class="mt-4">@csrf<button class="mv-button-secondary w-full">Arquivar perfil</button></form>
                            </section>
                        @endif
                    @endcan
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
