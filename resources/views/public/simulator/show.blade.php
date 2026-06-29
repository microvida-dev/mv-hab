<x-public-layout title="Simulador de candidatura" description="Simulação indicativa de elegibilidade, tipologia, renda e concursos compatíveis.">
    <section class="bg-ink-50 py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <p class="mv-caption">Simulador</p>
            <h1 class="mt-2 text-3xl font-semibold text-ink-900">Simular antes de candidatar</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">{{ $notices['public'] }}</p>
        </div>
    </section>

    <section class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('public.simulator.simulate') }}" class="mv-surface space-y-6 p-6">
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-ui.label for="contest_id">Concurso</x-ui.label>

                        <x-ui.select id="contest_id" name="contest_id" class="mt-1">
                            <option value="">Sem concurso específico</option>
                            @foreach ($contests as $contest)
                                <option value="{{ $contest->id }}" @selected(old('contest_id') == $contest->id)>
                                    {{ $contest->title }}
                                </option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.field-error name="contest_id" />
                    </div>

                    <div>
                        <x-ui.label for="housing_status">Situação habitacional</x-ui.label>

                        <x-ui.select id="housing_status" name="housing_status" class="mt-1" required>
                            <option value="">Selecione</option>
                            <option value="rented" @selected(old('housing_status') === 'rented')>Arrendamento</option>
                            <option value="family_home" @selected(old('housing_status') === 'family_home')>Casa de familiares</option>
                            <option value="temporary" @selected(old('housing_status') === 'temporary')>Alojamento temporário</option>
                            <option value="other" @selected(old('housing_status') === 'other')>Outra situação</option>
                        </x-ui.select>

                        <x-ui.field-error name="housing_status" />
                    </div>

                    <div>
                        <x-ui.label for="household_members_count">Elementos do agregado</x-ui.label>
                        <x-ui.input id="household_members_count" name="household_members_count" type="number" min="1" max="20" value="1" class="mt-1" required />
                        <x-ui.field-error name="household_members_count" />
                    </div>

                    <div>
                        <x-ui.label for="adults_count">Adultos</x-ui.label>
                        <x-ui.input id="adults_count" name="adults_count" type="number" min="1" max="20" value="1" class="mt-1" />
                        <x-ui.field-error name="adults_count" />
                    </div>

                    <div>
                        <x-ui.label for="dependents_count">Dependentes</x-ui.label>
                        <x-ui.input id="dependents_count" name="dependents_count" type="number" min="0" max="20" value="0" class="mt-1" />
                        <x-ui.field-error name="dependents_count" />
                    </div>

                    <div>
                        <x-ui.label for="disabled_members_count">Elementos com deficiência</x-ui.label>
                        <x-ui.input id="disabled_members_count" name="disabled_members_count" type="number" min="0" max="20" value="0" class="mt-1" />
                        <x-ui.field-error name="disabled_members_count" />
                    </div>

                    <div>
                        <x-ui.label for="monthly_income">Rendimento médio mensal</x-ui.label>
                        <x-ui.input id="monthly_income" name="monthly_income" type="number" min="0" step="0.01" class="mt-1" required />
                        <x-ui.field-error name="monthly_income" />
                    </div>

                    <div>
                        <x-ui.label for="current_monthly_rent">Renda mensal atual</x-ui.label>
                        <x-ui.input id="current_monthly_rent" name="current_monthly_rent" type="number" min="0" step="0.01" class="mt-1" />
                        <x-ui.field-error name="current_monthly_rent" />
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ([
                        'has_accessibility_needs' => 'Necessidades de acessibilidade',
                        'has_property' => 'Titularidade de imóvel habitacional',
                        'receives_housing_support' => 'Apoio público habitacional ativo',
                        'has_municipal_debt' => 'Dívida municipal sem acordo',
                    ] as $name => $label)
                        <label class="flex items-center gap-2 rounded-2xl border border-ink-100 bg-mvhab-surface px-3 py-2 text-sm text-ink-700">
                            <x-ui.checkbox :name="$name" />
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <label class="flex items-start gap-2 rounded-2xl border border-ink-100 bg-mvhab-surface px-3 py-3 text-sm text-ink-700">
                    <x-ui.checkbox name="privacy_notice_accepted" class="mt-1" required />
                    <span>Confirmo que compreendo o caráter indicativo da simulação e que não estou a submeter uma candidatura formal.</span>
                </label>

                <x-ui.field-error name="privacy_notice_accepted" />

                <div class="flex justify-end">
                    <button type="submit" class="mv-button-primary">Simular</button>
                </div>
            </form>
        </div>
    </section>
</x-public-layout>
