<?php

namespace App\Services\Regulatory\RentLimits;

use App\Data\Regulatory\RentLimitResult;
use App\Enums\AffordableRentLegalRegime;
use App\Enums\RentLimitConfigurationStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\RentRuleSet;
use App\Support\DecimalMoney;
use Carbon\CarbonInterface;

class PaaRentLimitProvider implements RentLimitProviderInterface
{
    public function supports(AffordableRentRegulatoryProfile $profile): bool
    {
        return $profile->legal_regime === AffordableRentLegalRegime::PaaLegacy2019;
    }

    public function limitsFor(
        AffordableRentRegulatoryProfile $profile,
        ?RentRuleSet $ruleSet,
        CarbonInterface $referenceDate,
    ): RentLimitResult {
        if (! $profile->rent_limits_configured) {
            return new RentLimitResult(
                RentLimitConfigurationStatus::Incomplete,
                null,
                null,
                $profile->source_version,
                'A tabela de limites de renda PAA não está configurada.',
            );
        }

        $effortRate = $ruleSet instanceof RentRuleSet
            ? $ruleSet->effort_rate_percentage
            : $profile->maximum_effort_rate_percentage;

        return new RentLimitResult(
            RentLimitConfigurationStatus::Configured,
            $ruleSet instanceof RentRuleSet && $ruleSet->minimum_rent !== null
                ? DecimalMoney::normalize($ruleSet->minimum_rent)
                : null,
            $ruleSet instanceof RentRuleSet && $ruleSet->maximum_rent !== null
                ? DecimalMoney::normalize($ruleSet->maximum_rent)
                : null,
            $profile->source_version,
            null,
            [
                'reference_date' => $referenceDate->toDateString(),
                'rent_rule_set_id' => $ruleSet instanceof RentRuleSet ? $ruleSet->id : null,
                'effort_rate_percentage' => $effortRate,
            ],
        );
    }
}
