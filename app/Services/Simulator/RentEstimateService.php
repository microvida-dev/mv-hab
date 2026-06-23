<?php

namespace App\Services\Simulator;

use App\Enums\RentEstimateStatus;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\RentRuleSet;
use App\Models\SimulatorConfiguration;

class RentEstimateService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{status: string, rent_min: float|null, rent_max: float|null, effort_rate: float|null, warnings: list<string>, payload: array<string, mixed>}
     */
    public function estimate(array $input, ?Contest $contest = null): array
    {
        $monthlyIncome = is_numeric($input['monthly_income'] ?? null) ? (float) $input['monthly_income'] : 0.0;

        if ($monthlyIncome <= 0.0) {
            return [
                'status' => RentEstimateStatus::InsufficientIncomeData->value,
                'rent_min' => null,
                'rent_max' => null,
                'effort_rate' => null,
                'warnings' => ['Indique rendimento mensal para calcular uma estimativa de renda.'],
                'payload' => ['source' => 'insufficient_income'],
            ];
        }

        $ruleSet = $this->activeRuleSet($contest);
        $configuration = SimulatorConfiguration::query()->active()->latest()->first();
        $ruleEffort = $ruleSet instanceof RentRuleSet ? $ruleSet->getAttribute('effort_rate_percentage') : null;
        $configurationEffort = $configuration instanceof SimulatorConfiguration ? $configuration->getAttribute('default_effort_rate') : null;
        $effortRate = (float) ($ruleEffort ?? $configurationEffort ?? 35);
        $incomeMaxRent = round(($monthlyIncome * $effortRate) / 100, 2);
        $unitRange = $this->unitRentRange($contest);

        $rentMin = $unitRange['min'];
        $rentMax = $unitRange['max'] !== null ? min($incomeMaxRent, $unitRange['max']) : $incomeMaxRent;

        if ($ruleSet instanceof RentRuleSet) {
            $minimumRent = $ruleSet->getAttribute('minimum_rent');
            $maximumRent = $ruleSet->getAttribute('maximum_rent');
            $rentMin = $minimumRent !== null ? max((float) $minimumRent, (float) ($rentMin ?? 0)) : $rentMin;
            $rentMax = $maximumRent !== null ? min((float) $maximumRent, $rentMax) : $rentMax;
        }

        $warnings = [];
        if ($unitRange['min'] !== null && $incomeMaxRent < $unitRange['min']) {
            $warnings[] = 'A renda máxima estimada pelo rendimento fica abaixo da renda mínima configurada para os fogos disponíveis.';
        }

        return [
            'status' => $warnings === [] ? RentEstimateStatus::Estimated->value : RentEstimateStatus::RequiresReview->value,
            'rent_min' => $rentMin,
            'rent_max' => max($rentMax, 0),
            'effort_rate' => $effortRate,
            'warnings' => $warnings,
            'payload' => [
                'source' => $ruleSet instanceof RentRuleSet ? 'rent_rule_set' : 'simulator_default',
                'monthly_income' => $monthlyIncome,
                'income_limited_max_rent' => $incomeMaxRent,
                'contest_id' => $contest?->id,
            ],
        ];
    }

    private function activeRuleSet(?Contest $contest): ?RentRuleSet
    {
        if (! $contest instanceof Contest) {
            return null;
        }

        return RentRuleSet::query()
            ->active()
            ->where(function ($query) use ($contest): void {
                $query->where('contest_id', $contest->id)
                    ->orWhere('program_id', $contest->program_id);
            })
            ->latest()
            ->first();
    }

    /**
     * @return array{min: float|null, max: float|null}
     */
    private function unitRentRange(?Contest $contest): array
    {
        if (! $contest instanceof Contest) {
            return ['min' => null, 'max' => null];
        }

        $query = ContestHousingUnit::query()
            ->where('contest_id', $contest->id)
            ->where('status', 'available')
            ->whereNotNull('monthly_rent');

        return [
            'min' => ($min = (clone $query)->min('monthly_rent')) !== null ? (float) $min : null,
            'max' => ($max = (clone $query)->max('monthly_rent')) !== null ? (float) $max : null,
        ];
    }
}
