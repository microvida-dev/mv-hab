<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Etapa 4 de 4"
            title="Habitação atual"
            description="Descreva a situação habitacional atual do agregado."
        >
            <x-slot name="actions">
                <a href="{{ route('candidate.current-housing.edit') }}" class="mv-button-primary">
                    {{ $situation ? 'Editar situação' : 'Preencher situação' }}
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <x-candidate.registration-stepper :registration="$registration->loadMissing(['household.members.incomeRecords', 'currentHousingSituation'])" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if (! $situation)
                <x-mv.section
                    title="Ainda não preencheu a sua situação habitacional atual."
                    description="Esta informação ajuda o município a compreender o contexto habitacional do agregado."
                >
                    <a href="{{ route('candidate.current-housing.edit') }}" class="mv-button-primary mt-5">Preencher situação</a>
                </x-mv.section>
            @else
                <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <x-mv.section :title="$situation->housing_status->label()">
                        <dl class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm text-ink-500">Município atual</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $situation->current_municipality ?: 'Não indicado' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-ink-500">Condição</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $situation->current_housing_condition?->label() ?? 'Não indicada' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-ink-500">Renda mensal atual</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $situation->current_monthly_rent !== null ? number_format((float) $situation->current_monthly_rent, 2, ',', '.').' €' : 'Não indicada' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-ink-500">Taxa de esforço aproximada</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $effortRate !== null ? number_format($effortRate, 1, ',', '.').'%' : 'Não calculável' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-sm text-ink-500">Motivo do pedido</dt>
                                <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-ink-900">{{ $situation->request_reason ?: 'Não indicado' }}</dd>
                            </div>
                        </dl>
                    </x-mv.section>

                    <aside class="space-y-4">
                        <x-mv.stat-card
                            label="Renda mensal atual"
                            :value="$situation->current_monthly_rent !== null ? number_format((float) $situation->current_monthly_rent, 2, ',', '.') . ' €' : 'Não indicada'"
                        />
                        <x-mv.stat-card
                            label="Taxa de esforço"
                            :value="$effortRate !== null ? number_format($effortRate, 1, ',', '.') . '%' : 'Não calculável'"
                        />

                        <x-mv.section title="Indicadores declarados" padding="p-5">
                            @foreach ([
                                [$situation->resides_in_municipality, 'Reside no município'],
                                [$situation->works_in_municipality, 'Trabalha no município'],
                                [$situation->is_overcrowded, 'Sobreocupação'],
                                [$situation->is_at_risk_of_eviction, 'Risco de perda de habitação'],
                                [$situation->has_accessibility_needs, 'Necessidades de acessibilidade'],
                                [$situation->has_high_rent_burden, 'Encargo habitacional elevado'],
                            ] as [$active, $label])
                                @if ($active)
                                    <x-mv.check-card :label="$label" :passed="true" class="mt-3" />
                                @endif
                            @endforeach
                        </x-mv.section>
                    </aside>
                </section>

                <x-mv.alert>
                    Esta informação é declarativa e preparatória. Não representa uma decisão de elegibilidade.
                </x-mv.alert>
            @endif
        </div>
    </div>
</x-app-layout>
