<?php

namespace App\Services\Regulatory\RentLimits;

use App\Data\Regulatory\RentLimitResult;
use App\Enums\AffordableRentLegalRegime;
use App\Enums\RentLimitConfigurationStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\RentRuleSet;
use App\Support\DecimalMoney;
use Carbon\CarbonInterface;

class RsaaRentLimitProvider implements RentLimitProviderInterface
{
    public function supports(AffordableRentRegulatoryProfile $profile): bool
    {
        return $profile->legal_regime === AffordableRentLegalRegime::Rsaa2026;
    }

    public function limitsFor(
        AffordableRentRegulatoryProfile $profile,
        ?RentRuleSet $ruleSet,
        CarbonInterface $referenceDate,
    ): RentLimitResult {
        if (
            ! $profile->rent_limits_configured
            || blank($profile->official_source)
            || blank($profile->source_version)
        ) {
            return new RentLimitResult(
                RentLimitConfigurationStatus::Incomplete,
                null,
                null,
                $profile->source_version,
                'A tabela oficial de limites de renda RSAA ainda não está configurada.',
            );
        }

        if (! $ruleSet instanceof RentRuleSet || $ruleSet->regulatory_profile_id !== $profile->id) {
            return new RentLimitResult(
                RentLimitConfigurationStatus::Incomplete,
                null,
                null,
                $profile->source_version,
                'Não existe um conjunto de regras de renda RSAA versionado para este contexto.',
            );
        }

        return new RentLimitResult(
            RentLimitConfigurationStatus::Configured,
            $ruleSet->minimum_rent !== null ? DecimalMoney::normalize($ruleSet->minimum_rent) : null,
            $ruleSet->maximum_rent !== null ? DecimalMoney::normalize($ruleSet->maximum_rent) : null,
            $profile->source_version,
            null,
            [
                'reference_date' => $referenceDate->toDateString(),
                'rent_rule_set_id' => $ruleSet->id,
                'effort_rate_percentage' => $ruleSet->effort_rate_percentage,
            ],
        );
    }
}
