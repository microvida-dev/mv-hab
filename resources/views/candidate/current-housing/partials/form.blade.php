<div class="space-y-8">
    <section>
        <h2 class="text-base font-semibold text-ink-900">Situação e localização</h2>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="housing_status" value="Situação habitacional *" />
                <select id="housing_status" name="housing_status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                    <option value="">Selecione</option>
                    @foreach ($housingStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('housing_status', $situation?->housing_status?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('housing_status')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="current_housing_condition" value="Condição da habitação" />
                <select id="current_housing_condition" name="current_housing_condition" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Selecione</option>
                    @foreach ($housingConditions as $value => $label)
                        <option value="{{ $value }}" @selected(old('current_housing_condition', $situation?->current_housing_condition?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <x-input-label for="current_address" value="Morada atual" />
                <x-text-input id="current_address" name="current_address" class="mt-1 block w-full" :value="old('current_address', $situation?->current_address)" />
            </div>
            <div>
                <x-input-label for="current_postal_code" value="Código postal" />
                <x-text-input id="current_postal_code" name="current_postal_code" class="mt-1 block w-full" :value="old('current_postal_code', $situation?->current_postal_code)" />
            </div>
            <div>
                <x-input-label for="current_city" value="Localidade" />
                <x-text-input id="current_city" name="current_city" class="mt-1 block w-full" :value="old('current_city', $situation?->current_city)" />
            </div>
            <div>
                <x-input-label for="current_parish" value="Freguesia" />
                <x-text-input id="current_parish" name="current_parish" class="mt-1 block w-full" :value="old('current_parish', $situation?->current_parish)" />
            </div>
            <div>
                <x-input-label for="current_municipality" value="Município" />
                <x-text-input id="current_municipality" name="current_municipality" class="mt-1 block w-full" :value="old('current_municipality', $situation?->current_municipality)" />
            </div>
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <h2 class="text-base font-semibold text-ink-900">Características e encargos</h2>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="current_housing_typology" value="Tipologia" />
                <x-text-input id="current_housing_typology" name="current_housing_typology" class="mt-1 block w-full" :value="old('current_housing_typology', $situation?->current_housing_typology)" placeholder="Ex.: T2" />
            </div>
            <div>
                <x-input-label for="current_housing_rooms" value="Número de quartos" />
                <x-text-input id="current_housing_rooms" name="current_housing_rooms" type="number" min="0" max="20" class="mt-1 block w-full" :value="old('current_housing_rooms', $situation?->current_housing_rooms)" />
            </div>
            <div>
                <x-input-label for="current_monthly_rent" value="Renda mensal atual" />
                <x-text-input id="current_monthly_rent" name="current_monthly_rent" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('current_monthly_rent', $situation?->current_monthly_rent)" />
                <x-input-error :messages="$errors->get('current_monthly_rent')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="current_housing_expense" value="Outros encargos mensais" />
                <x-text-input id="current_housing_expense" name="current_housing_expense" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('current_housing_expense', $situation?->current_housing_expense)" />
            </div>
            <div>
                <x-input-label for="residence_years_in_municipality" value="Anos de residência no município" />
                <x-text-input id="residence_years_in_municipality" name="residence_years_in_municipality" type="number" min="0" max="120" step="0.1" class="mt-1 block w-full" :value="old('residence_years_in_municipality', $situation?->residence_years_in_municipality)" />
            </div>
            <div>
                <x-input-label for="workplace_municipality" value="Município do local de trabalho" />
                <x-text-input id="workplace_municipality" name="workplace_municipality" class="mt-1 block w-full" :value="old('workplace_municipality', $situation?->workplace_municipality)" />
            </div>
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
                <label class="flex items-start gap-3 rounded-md border border-ink-100 p-3 text-sm text-ink-700">
                    <input type="checkbox" name="{{ $field }}" value="1" class="mt-0.5 rounded border-gray-300 text-civic-700" @checked(old($field, $situation?->{$field} ?? false))>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <div>
            <x-input-label for="request_reason" value="Motivo do pedido" />
            <textarea id="request_reason" name="request_reason" rows="5" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('request_reason', $situation?->request_reason) }}</textarea>
            <x-input-error :messages="$errors->get('request_reason')" class="mt-2" />
        </div>
        <div class="mt-5">
            <x-input-label for="additional_notes" value="Observações adicionais" />
            <textarea id="additional_notes" name="additional_notes" rows="4" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('additional_notes', $situation?->additional_notes) }}</textarea>
        </div>
    </section>
</div>
