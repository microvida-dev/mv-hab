<?php

namespace App\Services\Regulatory;

use App\Enums\RegulatoryConfigurationStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Support\DecimalMoney;
use Illuminate\Validation\ValidationException;

class MunicipalRegulatoryOverlayService
{
    /**
     * @return array<string, mixed>
     */
    public function effectiveParameters(AffordableRentRegulatoryProfile $profile): array
    {
        $profile->loadMissing('parentProfile');
        $parent = $profile->parentProfile;
        $parameters = $parent instanceof AffordableRentRegulatoryProfile
            ? $this->parameters($parent)
            : [];

        foreach ($this->parameters($profile) as $key => $value) {
            if ($value !== null) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }

    public function assertValid(AffordableRentRegulatoryProfile $profile): void
    {
        $profile->loadMissing('parentProfile');
        $parent = $profile->parentProfile;

        if ($profile->municipality_id === null) {
            if ($parent !== null) {
                $this->fail('Um perfil nacional não pode depender de um overlay municipal.');
            }

            return;
        }

        if (! $parent instanceof AffordableRentRegulatoryProfile) {
            $this->fail('O overlay municipal deve indicar o perfil regulamentar nacional.');
        }

        if ($profile->legal_regime !== $parent->legal_regime) {
            $this->fail('O overlay municipal não pode alterar o regime do perfil nacional.');
        }

        $this->assertMaximumNotWeakened($profile, $parent, 'maximum_effort_rate_percentage', 'taxa máxima de esforço');
        $this->assertMaximumNotWeakened($profile, $parent, 'annual_income_base_limit', 'limite anual base');
        $this->assertMaximumNotWeakened($profile, $parent, 'second_person_increment', 'acréscimo da segunda pessoa');
        $this->assertMaximumNotWeakened($profile, $parent, 'additional_person_increment', 'acréscimo por pessoa adicional');
        $this->assertMaximumNotWeakened($profile, $parent, 'sixth_irs_bracket_upper_limit', 'limite superior do 6.º escalão do IRS');
        $this->assertMinimumNotWeakened($profile, $parent, 'minimum_adult_monthly_income', 'rendimento mínimo de adulto');
        $this->assertIntegerMinimumNotWeakened($profile, $parent, 'minimum_contract_months', 'prazo contratual mínimo');
        $this->assertIntegerMinimumNotWeakened($profile, $parent, 'standard_contract_months', 'prazo contratual normal');

        foreach ([
            'rent_limits_configured' => 'limites de renda',
            'eligibility_rules_configured' => 'regras de elegibilidade',
            'typology_rules_configured' => 'regras de tipologia',
            'contract_terms_configured' => 'termos contratuais',
        ] as $field => $label) {
            if ($parent->{$field} && ! $profile->{$field}) {
                $this->fail("O overlay municipal não pode desativar {$label} nacionais.");
            }
        }

        if (
            $parent->configuration_status !== RegulatoryConfigurationStatus::Complete
            && $profile->configuration_status === RegulatoryConfigurationStatus::Complete
            && ! $this->isExplicitDemoOverlay($profile)
        ) {
            $this->fail('Um overlay municipal não pode declarar completa uma configuração nacional incompleta.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(AffordableRentRegulatoryProfile $profile): array
    {
        $profile->loadMissing('parentProfile');

        if ($profile->municipality_id === null) {
            return [];
        }

        return [
            'profile_id' => $profile->id,
            'code' => $profile->code,
            'version' => $profile->version,
            'municipality_id' => $profile->municipality_id,
            'parent_profile_id' => $profile->parent_profile_id,
            'parent_code' => $profile->parentProfile?->code,
            'parameters' => $this->parameters($profile),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parameters(AffordableRentRegulatoryProfile $profile): array
    {
        return [
            'maximum_effort_rate_percentage' => $profile->maximum_effort_rate_percentage,
            'minimum_adult_monthly_income' => $profile->minimum_adult_monthly_income,
            'annual_income_base_limit' => $profile->annual_income_base_limit,
            'second_person_increment' => $profile->second_person_increment,
            'additional_person_increment' => $profile->additional_person_increment,
            'tax_year' => $profile->tax_year,
            'sixth_irs_bracket_upper_limit' => $profile->sixth_irs_bracket_upper_limit,
            'irs_source_reference' => $profile->irs_source_reference,
            'irs_source_version' => $profile->irs_source_version,
            'irs_effective_from' => $profile->irs_effective_from?->toDateString(),
            'irs_effective_until' => $profile->irs_effective_until?->toDateString(),
            'minimum_contract_months' => $profile->minimum_contract_months,
            'standard_contract_months' => $profile->standard_contract_months,
            'rent_limits_configured' => $profile->rent_limits_configured,
            'eligibility_rules_configured' => $profile->eligibility_rules_configured,
            'typology_rules_configured' => $profile->typology_rules_configured,
            'contract_terms_configured' => $profile->contract_terms_configured,
            'metadata' => $profile->metadata ?? [],
        ];
    }

    private function assertMaximumNotWeakened(
        AffordableRentRegulatoryProfile $profile,
        AffordableRentRegulatoryProfile $parent,
        string $field,
        string $label,
    ): void {
        if (
            $profile->{$field} !== null
            && $parent->{$field} !== null
            && DecimalMoney::compare((string) $profile->{$field}, (string) $parent->{$field}) === 1
        ) {
            $this->fail("O overlay municipal não pode aumentar {$label}.");
        }
    }

    private function assertMinimumNotWeakened(
        AffordableRentRegulatoryProfile $profile,
        AffordableRentRegulatoryProfile $parent,
        string $field,
        string $label,
    ): void {
        if (
            $profile->{$field} !== null
            && $parent->{$field} !== null
            && DecimalMoney::compare((string) $profile->{$field}, (string) $parent->{$field}) === -1
        ) {
            $this->fail("O overlay municipal não pode reduzir {$label}.");
        }
    }

    private function assertIntegerMinimumNotWeakened(
        AffordableRentRegulatoryProfile $profile,
        AffordableRentRegulatoryProfile $parent,
        string $field,
        string $label,
    ): void {
        if (
            $profile->{$field} !== null
            && $parent->{$field} !== null
            && (int) $profile->{$field} < (int) $parent->{$field}
        ) {
            $this->fail("O overlay municipal não pode reduzir {$label}.");
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['regulatory_profile_id' => $message]);
    }

    private function isExplicitDemoOverlay(
        AffordableRentRegulatoryProfile $profile,
    ): bool {
        return config('mvhab.regulatory_demo_mode', false)
            && (bool) data_get($profile->metadata, 'demo', false)
            && (bool) data_get($profile->metadata, 'demo_only', false);
    }
}
