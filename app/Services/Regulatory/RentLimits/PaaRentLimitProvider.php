<?php

namespace App\Services\Regulatory\RentLimits;

use App\Data\Regulatory\RentLimitResult;
use App\Enums\AffordableRentLegalRegime;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\RentRuleSet;
use Carbon\CarbonInterface;

class PaaRentLimitProvider implements RentLimitProviderInterface
{
    public function __construct(
        private readonly RentLimitTableAuditService $tableAudit,
    ) {}

    public function supports(AffordableRentRegulatoryProfile $profile): bool
    {
        return $profile->legal_regime === AffordableRentLegalRegime::PaaLegacy2019;
    }

    public function limitsFor(
        AffordableRentRegulatoryProfile $profile,
        ?RentRuleSet $ruleSet,
        CarbonInterface $referenceDate,
    ): RentLimitResult {
        $audit = $this->tableAudit->audit($profile, $ruleSet, $referenceDate);

        return new RentLimitResult(
            $audit->status,
            $audit->minimumRent,
            $audit->maximumRent,
            $audit->sourceVersion,
            $audit->isConfigured()
                ? null
                : ($audit->findings[0] ?? 'A tabela de limites de renda PAA não está configurada.'),
            [
                'reference_date' => $referenceDate->toDateString(),
                'rent_rule_set_id' => $ruleSet instanceof RentRuleSet ? $ruleSet->id : null,
                'effort_rate_percentage' => $ruleSet?->effort_rate_percentage,
                'manifest' => $audit->toArray(),
            ],
        );
    }
}
