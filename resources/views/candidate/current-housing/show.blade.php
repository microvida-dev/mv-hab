<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Etapa 4 de 4</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Habitação atual</h1>
                <p class="mt-1 text-sm text-ink-500">Descreva a situação habitacional atual do agregado.</p>
            </div>
            <a href="{{ route('candidate.current-housing.edit') }}" class="mv-button-primary">
                {{ $situation ? 'Editar situação' : 'Preencher situação' }}
            </a>
        </div>
    </x-slot>

    <x-candidate.registration-stepper :registration="$registration->loadMissing(['household.members.incomeRecords', 'currentHousingSituation'])" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if (! $situation)
                <section class="mv-surface p-6">
                    <h2 class="text-xl font-semibold text-ink-900">Ainda não preencheu a sua situação habitacional atual.</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-600">Esta informação ajuda o município a compreender o contexto habitacional do agregado.</p>
                    <a href="{{ route('candidate.current-housing.edit') }}" class="mv-button-primary mt-5">Preencher situação</a>
                </section>
            @else
                <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <div class="mv-surface p-6">
                        <h2 class="text-xl font-semibold text-ink-900">{{ $situation->housing_status->label() }}</h2>
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
                    </div>

                    <aside class="mv-surface p-5">
                        <h2 class="font-semibold text-ink-900">Indicadores declarados</h2>
                        <ul class="mt-4 space-y-3 text-sm text-ink-600">
                            @foreach ([
                                [$situation->resides_in_municipality, 'Reside no município'],
                                [$situation->works_in_municipality, 'Trabalha no município'],
                                [$situation->is_overcrowded, 'Sobreocupação'],
                                [$situation->is_at_risk_of_eviction, 'Risco de perda de habitação'],
                                [$situation->has_accessibility_needs, 'Necessidades de acessibilidade'],
                                [$situation->has_high_rent_burden, 'Encargo habitacional elevado'],
                            ] as [$active, $label])
                                @if ($active)
                                    <li class="flex gap-2"><x-ui-icon name="check" class="h-4 w-4 shrink-0 text-civic-700" />{{ $label }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </aside>
                </section>

                <p class="text-xs leading-5 text-ink-500">Esta informação é declarativa e preparatória. Não representa uma decisão de elegibilidade.</p>
            @endif
        </div>
    </div>
</x-app-layout>
