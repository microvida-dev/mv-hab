<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Simulador"
            title="Nova simulação"
            :description="$notices['short']"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route('candidate.simulations.store') }}"
                class="space-y-6"
                x-data="{ useRegistrationData: {{ old('use_registration_data', true) ? 'true' : 'false' }} }"
            >
                @csrf

                @if ($prefillAvailable)
                    <x-mv.section title="Dados do Registo de Adesão">
                        <x-mv.checkbox-card
                            name="use_registration_data"
                            label="Utilizar os dados do meu Registo de Adesão nesta simulação."
                            :checked="old('use_registration_data', true)"
                            x-model="useRegistrationData"
                        />

                        <p class="mt-3 text-sm text-ink-600">
                            Os campos abaixo foram preenchidos automaticamente com os dados do seu
                            Registo de Adesão. Desative esta opção para criar uma simulação
                            personalizada sem alterar os dados do seu registo.
                        </p>
                    </x-mv.section>
                @endif

                <x-mv.section title="Dados da simulação">
                    <div class="grid gap-4 md:grid-cols-2">

                        <label class="block">
                            <span class="text-sm font-semibold text-ink-800">Concurso</span>

                            <select
                                name="contest_id"
                                class="mv-input mt-1 w-full"
                            >
                                <option value="">Recomendar automaticamente</option>

                                @foreach ($contests as $contest)
                                    <option
                                        value="{{ $contest->id }}"
                                        @selected(old('contest_id') == $contest->id)
                                    >
                                        {{ $contest->title }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-ink-800">
                                Situação habitacional
                            </span>

                            <select
                                name="housing_status"
                                class="mv-input mt-1 w-full"
                                x-bind:disabled="useRegistrationData"
                                x-bind:class="useRegistrationData ? 'bg-mvhab-surface cursor-not-allowed' : ''"
                                required
                            >
                                <option value="">Selecionar situação habitacional</option>

                                @foreach ($housingStatuses as $value => $label)
                                    <option
                                        value="{{ $value }}"
                                        @selected(old('housing_status', $prefill['housing_status'] ?? null) === $value)
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-ink-800">
                                Elementos
                            </span>

                            <input
                                type="number"
                                min="1"
                                max="20"
                                name="household_members_count"
                                value="{{ old('household_members_count', $prefill['household_members_count'] ?? 1) }}"
                                class="mv-input mt-1 w-full"
                                x-bind:disabled="useRegistrationData"
                                x-bind:class="useRegistrationData ? 'bg-mvhab-surface cursor-not-allowed' : ''"
                                required
                            >
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-ink-800">
                                Adultos
                            </span>

                            <input
                                type="number"
                                min="1"
                                max="20"
                                name="adults_count"
                                value="{{ old('adults_count', $prefill['adults_count'] ?? 1) }}"
                                class="mv-input mt-1 w-full"
                                x-bind:disabled="useRegistrationData"
                                x-bind:class="useRegistrationData ? 'bg-mvhab-surface cursor-not-allowed' : ''"
                            >
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-ink-800">
                                Dependentes
                            </span>

                            <input
                                type="number"
                                min="0"
                                max="20"
                                name="dependents_count"
                                value="{{ old('dependents_count', $prefill['dependents_count'] ?? 0) }}"
                                class="mv-input mt-1 w-full"
                                x-bind:disabled="useRegistrationData"
                                x-bind:class="useRegistrationData ? 'bg-mvhab-surface cursor-not-allowed' : ''"
                            >
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-ink-800">
                                Rendimento mensal (€)
                            </span>

                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                name="monthly_income"
                                value="{{ old('monthly_income', $prefill['monthly_income'] ?? null) }}"
                                class="mv-input mt-1 w-full"
                                x-bind:disabled="useRegistrationData"
                                x-bind:class="useRegistrationData ? 'bg-mvhab-surface cursor-not-allowed' : ''"
                                required
                            >
                        </label>

                    </div>
                </x-mv.section>

                <x-mv.checkbox-card
                    name="privacy_notice_accepted"
                    label="Confirmo que compreendo que esta simulação é meramente indicativa e não substitui a análise oficial de uma candidatura."
                    :checked="old('privacy_notice_accepted', false)"
                    align="start"
                />

                <div class="flex justify-end">
                    <button class="mv-button-primary">
                        Simular elegibilidade
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
