<div class="space-y-8">
    <section>
        <h2 class="text-base font-semibold text-ink-900">Situação e localização</h2>

        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <x-ui.field for="housing_status" name="housing_status" label="Situação habitacional" required>
                <x-ui.select id="housing_status" name="housing_status" required>
                    <option value="">Selecione</option>
                    @foreach ($housingStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('housing_status', $situation?->housing_status?->value) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.field for="current_housing_condition" name="current_housing_condition" label="Condição da habitação">
                <x-ui.select id="current_housing_condition" name="current_housing_condition">
                    <option value="">Selecione</option>
                    @foreach ($housingConditions as $value => $label)
                        <option value="{{ $value }}" @selected(old('current_housing_condition', $situation?->current_housing_condition?->value) === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.field for="current_address" name="current_address" label="Morada atual" class="md:col-span-2">
                <x-ui.input
                    id="current_address"
                    name="current_address"
                    :value="old('current_address', $situation?->current_address)"
                />
            </x-ui.field>

            <x-ui.field for="current_postal_code" name="current_postal_code" label="Código postal">
                <x-ui.input
                    id="current_postal_code"
                    name="current_postal_code"
                    :value="old('current_postal_code', $situation?->current_postal_code)"
                />
            </x-ui.field>

            <x-ui.field for="current_city" name="current_city" label="Localidade">
                <x-ui.input
                    id="current_city"
                    name="current_city"
                    :value="old('current_city', $situation?->current_city)"
                />
            </x-ui.field>

            <x-ui.field for="current_parish" name="current_parish" label="Freguesia">
                <x-ui.input
                    id="current_parish"
                    name="current_parish"
                    :value="old('current_parish', $situation?->current_parish)"
                />
            </x-ui.field>

            <x-ui.field for="current_municipality" name="current_municipality" label="Município">
                <x-ui.input
                    id="current_municipality"
                    name="current_municipality"
                    :value="old('current_municipality', $situation?->current_municipality)"
                />
            </x-ui.field>
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <h2 class="text-base font-semibold text-ink-900">Características e encargos</h2>

        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <x-ui.field for="current_housing_typology" name="current_housing_typology" label="Tipologia">
                <x-ui.input
                    id="current_housing_typology"
                    name="current_housing_typology"
                    :value="old('current_housing_typology', $situation?->current_housing_typology)"
                    placeholder="Ex.: T2"
                />
            </x-ui.field>

            <x-ui.field for="current_housing_rooms" name="current_housing_rooms" label="Número de quartos">
                <x-ui.input
                    id="current_housing_rooms"
                    name="current_housing_rooms"
                    type="number"
                    min="0"
                    max="20"
                    :value="old('current_housing_rooms', $situation?->current_housing_rooms)"
                />
            </x-ui.field>

            <x-ui.field for="current_monthly_rent" name="current_monthly_rent" label="Renda mensal atual">
                <x-ui.input
                    id="current_monthly_rent"
                    name="current_monthly_rent"
                    type="number"
                    min="0"
                    step="0.01"
                    :value="old('current_monthly_rent', $situation?->current_monthly_rent)"
                />
            </x-ui.field>

            <x-ui.field for="current_housing_expense" name="current_housing_expense" label="Outros encargos mensais">
                <x-ui.input
                    id="current_housing_expense"
                    name="current_housing_expense"
                    type="number"
                    min="0"
                    step="0.01"
                    :value="old('current_housing_expense', $situation?->current_housing_expense)"
                />
            </x-ui.field>

            <x-ui.field for="residence_years_in_municipality" name="residence_years_in_municipality" label="Anos de residência no município">
                <x-ui.input
                    id="residence_years_in_municipality"
                    name="residence_years_in_municipality"
                    type="number"
                    min="0"
                    max="120"
                    step="0.1"
                    :value="old('residence_years_in_municipality', $situation?->residence_years_in_municipality)"
                />
            </x-ui.field>

            <x-ui.field for="workplace_municipality" name="workplace_municipality" label="Município do local de trabalho">
                <x-ui.input
                    id="workplace_municipality"
                    name="workplace_municipality"
                    :value="old('workplace_municipality', $situation?->workplace_municipality)"
                />
            </x-ui.field>
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <h2 class="text-base font-semibold text-ink-900">Indicadores declarados</h2>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ([
                'resides_in_municipality' => 'Reside no município',
                'works_in_municipality' => 'Trabalha no município',
                'is_overcrowded' => 'Habitação sobreocupada',
                'is_at_risk_of_eviction' => 'Risco de perda da habitação',
                'is_homeless' => 'Situação sem habitação',
                'is_temporary_accommodation' => 'Alojamento temporário',
                'is_domestic_violence_victim' => 'Situação de violência doméstica',
                'has_accessibility_needs' => 'Necessidades de acessibilidade',
                'has_high_rent_burden' => 'Encargo habitacional elevado',
            ] as $field => $label)
                <label class="flex items-start gap-3 rounded-2xl border border-ink-100 p-3 text-sm text-ink-700">
                    <x-ui.checkbox
                        name="{{ $field }}"
                        value="1"
                        class="mt-0.5"
                        @checked(old($field, $situation?->{$field} ?? false))
                    />
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <x-ui.field for="request_reason" name="request_reason" label="Motivo do pedido">
            <x-ui.textarea id="request_reason" name="request_reason" rows="5">{{ old('request_reason', $situation?->request_reason) }}</x-ui.textarea>
        </x-ui.field>

        <x-ui.field for="additional_notes" name="additional_notes" label="Observações adicionais" class="mt-5">
            <x-ui.textarea id="additional_notes" name="additional_notes" rows="4">{{ old('additional_notes', $situation?->additional_notes) }}</x-ui.textarea>
        </x-ui.field>
    </section>
</div>
