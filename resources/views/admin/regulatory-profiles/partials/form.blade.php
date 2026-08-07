@php
    $scopeType = old('scope_type', isset($regulatoryProfile) && $regulatoryProfile->municipality_id !== null ? 'municipal' : 'national');
@endphp

<x-mv.alert tone="warning" class="mb-6">
    Esta configuração determina a base regulamentar utilizada na publicação. Utilize apenas fontes oficiais verificadas; perfis já fixados em snapshots não podem ser alterados.
</x-mv.alert>

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <x-input-label for="scope_type" value="Âmbito" />
        <select id="scope_type" name="scope_type" class="mv-input mt-1 block w-full" required>
            <option value="national" @selected($scopeType === 'national')>Nacional</option>
            <option value="municipal" @selected($scopeType === 'municipal')>Municipal — {{ $municipality->name }}</option>
        </select>
        <p class="mt-1 text-xs text-ink-500">Os perfis municipais são overlays mais restritivos sobre um perfil nacional.</p>
    </div>

    <div>
        <x-input-label for="parent_profile_id" value="Perfil nacional de origem" />
        <select id="parent_profile_id" name="parent_profile_id" class="mv-input mt-1 block w-full">
            <option value="">Sem perfil pai — perfil nacional</option>
            @foreach ($parentProfiles as $parentProfile)
                <option value="{{ $parentProfile->id }}" @selected((string) old('parent_profile_id', $regulatoryProfile->parent_profile_id ?? '') === (string) $parentProfile->id)>
                    {{ $parentProfile->legal_regime->label() }} · {{ $parentProfile->name }} · {{ $parentProfile->version }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('parent_profile_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="legal_regime" value="Regime legal" />
        <select id="legal_regime" name="legal_regime" class="mv-input mt-1 block w-full" required>
            @foreach ($legalRegimes as $value => $label)
                <option value="{{ $value }}" @selected(old('legal_regime', isset($regulatoryProfile) ? $regulatoryProfile->legal_regime->value : '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('legal_regime')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="configuration_status" value="Completude" />
        <select id="configuration_status" name="configuration_status" class="mv-input mt-1 block w-full" required>
            @foreach ($configurationStatuses as $value => $label)
                <option value="{{ $value }}" @selected(old('configuration_status', isset($regulatoryProfile) ? $regulatoryProfile->configuration_status->value : 'incomplete') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('configuration_status')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="code" value="Código" />
        <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $regulatoryProfile->code ?? '')" required />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="version" value="Versão" />
        <x-text-input id="version" name="version" class="mt-1 block w-full" :value="old('version', $regulatoryProfile->version ?? '')" required />
        <x-input-error :messages="$errors->get('version')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="name" value="Nome do perfil" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $regulatoryProfile->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="effective_from" value="Vigente desde" />
        <x-text-input id="effective_from" name="effective_from" type="date" class="mt-1 block w-full" :value="old('effective_from', isset($regulatoryProfile) ? $regulatoryProfile->effective_from?->format('Y-m-d') : '')" required />
        <x-input-error :messages="$errors->get('effective_from')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="effective_until" value="Vigente até" />
        <x-text-input id="effective_until" name="effective_until" type="date" class="mt-1 block w-full" :value="old('effective_until', isset($regulatoryProfile) ? $regulatoryProfile->effective_until?->format('Y-m-d') : '')" />
        <x-input-error :messages="$errors->get('effective_until')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="legal_basis" value="Base legal" />
        <textarea id="legal_basis" name="legal_basis" rows="4" class="mv-input mt-1 block w-full" required>{{ old('legal_basis', $regulatoryProfile->legal_basis ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('legal_basis')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="official_source" value="Fonte oficial" />
        <textarea id="official_source" name="official_source" rows="3" class="mv-input mt-1 block w-full">{{ old('official_source', $regulatoryProfile->official_source ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('official_source')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="publication_reference" value="Referência de publicação" />
        <x-text-input id="publication_reference" name="publication_reference" class="mt-1 block w-full" :value="old('publication_reference', $regulatoryProfile->publication_reference ?? '')" />
    </div>

    <div>
        <x-input-label for="source_version" value="Versão da fonte" />
        <x-text-input id="source_version" name="source_version" class="mt-1 block w-full" :value="old('source_version', $regulatoryProfile->source_version ?? '')" />
        <p class="mt-1 text-xs text-ink-500">A tabela de rendas terá de usar exatamente esta versão.</p>
    </div>
</div>

<section class="mt-8 border-t border-ink-100 pt-6">
    <h2 class="text-lg font-semibold text-ink-900">Limites económicos</h2>
    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div><x-input-label for="maximum_effort_rate_percentage" value="Taxa máxima de esforço (%)" /><x-text-input id="maximum_effort_rate_percentage" name="maximum_effort_rate_percentage" type="number" step="0.01" class="mt-1 w-full" :value="old('maximum_effort_rate_percentage', $regulatoryProfile->maximum_effort_rate_percentage ?? '')" /></div>
        <div><x-input-label for="minimum_adult_monthly_income" value="Rendimento mínimo adulto/mês" /><x-text-input id="minimum_adult_monthly_income" name="minimum_adult_monthly_income" type="number" step="0.01" class="mt-1 w-full" :value="old('minimum_adult_monthly_income', $regulatoryProfile->minimum_adult_monthly_income ?? '')" /></div>
        <div><x-input-label for="annual_income_base_limit" value="Limite anual base" /><x-text-input id="annual_income_base_limit" name="annual_income_base_limit" type="number" step="0.01" class="mt-1 w-full" :value="old('annual_income_base_limit', $regulatoryProfile->annual_income_base_limit ?? '')" /></div>
        <div><x-input-label for="second_person_increment" value="Incremento 2.ª pessoa" /><x-text-input id="second_person_increment" name="second_person_increment" type="number" step="0.01" class="mt-1 w-full" :value="old('second_person_increment', $regulatoryProfile->second_person_increment ?? '')" /></div>
        <div><x-input-label for="additional_person_increment" value="Incremento por pessoa adicional" /><x-text-input id="additional_person_increment" name="additional_person_increment" type="number" step="0.01" class="mt-1 w-full" :value="old('additional_person_increment', $regulatoryProfile->additional_person_increment ?? '')" /></div>
        <div><x-input-label for="tax_year" value="Ano fiscal" /><x-text-input id="tax_year" name="tax_year" type="number" class="mt-1 w-full" :value="old('tax_year', $regulatoryProfile->tax_year ?? '')" /></div>
        <div><x-input-label for="sixth_irs_bracket_upper_limit" value="Limite superior do 6.º escalão IRS" /><x-text-input id="sixth_irs_bracket_upper_limit" name="sixth_irs_bracket_upper_limit" type="number" step="0.01" class="mt-1 w-full" :value="old('sixth_irs_bracket_upper_limit', $regulatoryProfile->sixth_irs_bracket_upper_limit ?? '')" /></div>
        <div><x-input-label for="minimum_contract_months" value="Contrato mínimo (meses)" /><x-text-input id="minimum_contract_months" name="minimum_contract_months" type="number" class="mt-1 w-full" :value="old('minimum_contract_months', $regulatoryProfile->minimum_contract_months ?? '')" /></div>
        <div><x-input-label for="standard_contract_months" value="Contrato padrão (meses)" /><x-text-input id="standard_contract_months" name="standard_contract_months" type="number" class="mt-1 w-full" :value="old('standard_contract_months', $regulatoryProfile->standard_contract_months ?? '')" /></div>
    </div>
</section>

<section class="mt-8 border-t border-ink-100 pt-6">
    <h2 class="text-lg font-semibold text-ink-900">Fonte IRS</h2>
    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <div><x-input-label for="irs_source_reference" value="Referência IRS" /><x-text-input id="irs_source_reference" name="irs_source_reference" class="mt-1 w-full" :value="old('irs_source_reference', $regulatoryProfile->irs_source_reference ?? '')" /></div>
        <div><x-input-label for="irs_source_version" value="Versão IRS" /><x-text-input id="irs_source_version" name="irs_source_version" class="mt-1 w-full" :value="old('irs_source_version', $regulatoryProfile->irs_source_version ?? '')" /></div>
        <div><x-input-label for="irs_effective_from" value="IRS vigente desde" /><x-text-input id="irs_effective_from" name="irs_effective_from" type="date" class="mt-1 w-full" :value="old('irs_effective_from', isset($regulatoryProfile) ? $regulatoryProfile->irs_effective_from?->format('Y-m-d') : '')" /></div>
        <div><x-input-label for="irs_effective_until" value="IRS vigente até" /><x-text-input id="irs_effective_until" name="irs_effective_until" type="date" class="mt-1 w-full" :value="old('irs_effective_until', isset($regulatoryProfile) ? $regulatoryProfile->irs_effective_until?->format('Y-m-d') : '')" /></div>
    </div>
</section>

<section class="mt-8 border-t border-ink-100 pt-6">
    <h2 class="text-lg font-semibold text-ink-900">Componentes configurados</h2>
    <p class="mt-1 text-sm text-ink-500">Marque apenas componentes sustentados por configuração e fonte verificadas. A publicação continua a executar validações técnicas adicionais.</p>
    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        @foreach ([
            'rent_limits_configured' => 'Limites de renda',
            'eligibility_rules_configured' => 'Elegibilidade',
            'typology_rules_configured' => 'Tipologia',
            'contract_terms_configured' => 'Termos contratuais',
        ] as $field => $label)
            <label class="flex items-center gap-3 rounded-2xl border border-ink-100 bg-ink-50 p-4 text-sm font-semibold text-ink-700">
                <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-ink-300 text-mvhab-primary" @checked(old($field, $regulatoryProfile->{$field} ?? false))>
                {{ $label }}
            </label>
        @endforeach
    </div>
</section>

<div class="mt-8 flex justify-end gap-3">
    <a href="{{ route('admin.regulatory-profiles.index') }}" class="mv-button-secondary">Cancelar</a>
    <button type="submit" class="mv-button-primary">{{ $submitLabel }}</button>
</div>
