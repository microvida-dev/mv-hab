<x-public-layout title="Simulador de candidatura" description="Simulação indicativa de elegibilidade, tipologia, renda e concursos compatíveis.">
    <section class="bg-ink-50 py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold text-civic-700">Simulador</p>
            <h1 class="mt-2 text-3xl font-semibold text-ink-900">Simular antes de candidatar</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">{{ $notices['public'] }}</p>
        </div>
    </section>

    <section class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('public.simulator.simulate') }}" class="mv-surface space-y-6 p-6">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-ink-800">Concurso</span>
                        <select name="contest_id" class="mt-1 w-full rounded-md border-ink-200">
                            <option value="">Qualquer concurso publicado</option>
                            @foreach ($contests as $contest)
                                <option value="{{ $contest->id }}" @selected(old('contest_id') == $contest->id)>{{ $contest->title }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink-800">Situação habitacional</span>
                        <select name="housing_status" class="mt-1 w-full rounded-md border-ink-200" required>
                            <option value="">Selecione</option>
                            <option value="rented" @selected(old('housing_status') === 'rented')>Arrendamento</option>
                            <option value="family_home" @selected(old('housing_status') === 'family_home')>Casa de familiares</option>
                            <option value="temporary" @selected(old('housing_status') === 'temporary')>Alojamento temporário</option>
                            <option value="other" @selected(old('housing_status') === 'other')>Outra situação</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink-800">Elementos do agregado</span>
                        <input type="number" min="1" max="20" name="household_members_count" value="{{ old('household_members_count', 1) }}" class="mt-1 w-full rounded-md border-ink-200" required>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink-800">Adultos</span>
                        <input type="number" min="1" max="20" name="adults_count" value="{{ old('adults_count', 1) }}" class="mt-1 w-full rounded-md border-ink-200">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink-800">Dependentes</span>
                        <input type="number" min="0" max="20" name="dependents_count" value="{{ old('dependents_count', 0) }}" class="mt-1 w-full rounded-md border-ink-200">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink-800">Elementos com deficiência</span>
                        <input type="number" min="0" max="20" name="disabled_members_count" value="{{ old('disabled_members_count', 0) }}" class="mt-1 w-full rounded-md border-ink-200">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink-800">Rendimento médio mensal</span>
                        <input type="number" min="0" step="0.01" name="monthly_income" value="{{ old('monthly_income') }}" class="mt-1 w-full rounded-md border-ink-200" required>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink-800">Renda mensal atual</span>
                        <input type="number" min="0" step="0.01" name="current_monthly_rent" value="{{ old('current_monthly_rent') }}" class="mt-1 w-full rounded-md border-ink-200">
                    </label>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ([
                        'has_accessibility_needs' => 'Necessidades de acessibilidade',
                        'has_property' => 'Titularidade de imóvel habitacional',
                        'receives_housing_support' => 'Apoio público habitacional ativo',
                        'has_municipal_debt' => 'Dívida municipal sem acordo',
                    ] as $name => $label)
                        <label class="flex items-center gap-2 text-sm text-ink-700">
                            <input type="checkbox" name="{{ $name }}" value="1" class="rounded border-ink-300 text-civic-700" @checked(old($name))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>

                <label class="flex items-start gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="privacy_notice_accepted" value="1" class="mt-1 rounded border-ink-300 text-civic-700" required>
                    <span>Confirmo que compreendo o caráter indicativo da simulação e que não estou a submeter uma candidatura formal.</span>
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="mv-button-primary">Simular</button>
                </div>
            </form>
        </div>
    </section>
</x-public-layout>
