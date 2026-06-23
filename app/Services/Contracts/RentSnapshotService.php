<?php

namespace App\Services\Contracts;

use App\Models\Allocation;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\Household;
use App\Models\HousingUnit;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Models\RentRuleSet;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class RentSnapshotService
{
    /**
     * @return array<string, mixed>
     */
    public function forAllocation(Allocation $allocation, RentRuleSet $ruleSet): array
    {
        $allocation->loadMissing([
            'application.adhesionRegistration',
            'application.household.members',
            'application.household.incomeRecords.incomeSource',
            'application.currentHousingSituation',
            'candidate',
            'housingUnit',
            'contestHousingUnit',
            'contest',
            'program',
        ]);

        /** @var Application|null $application */
        $application = $allocation->getRelationValue('application');
        /** @var Household|null $household */
        $household = $application?->getRelationValue('household');
        /** @var EloquentCollection<int, IncomeRecord> $incomeRecords */
        $incomeRecords = $household instanceof Household ? $household->incomeRecords : new EloquentCollection;
        /** @var User|null $candidate */
        $candidate = $allocation->getRelationValue('candidate');
        /** @var HousingUnit|null $housingUnit */
        $housingUnit = $allocation->getRelationValue('housingUnit');
        /** @var ContestHousingUnit|null $contestHousingUnit */
        $contestHousingUnit = $allocation->getRelationValue('contestHousingUnit');

        return [
            'calculated_at' => now()->toIso8601String(),
            'application' => [
                'id' => $allocation->application_id,
                'application_number' => $application?->application_number,
            ],
            'candidate' => [
                'id' => $allocation->user_id,
                'name' => $candidate?->name,
            ],
            'household' => [
                'id' => $household?->id,
                'members_count' => $household instanceof Household ? ($household->members_count ?? $household->members->count()) : 0,
                'monthly_income' => (float) $incomeRecords->sum('monthly_amount'),
                'annual_income' => (float) $incomeRecords->sum('annual_amount'),
            ],
            'income_records' => $incomeRecords->map(function (IncomeRecord $record): array {
                /** @var IncomeSource|null $incomeSource */
                $incomeSource = $record->getRelationValue('incomeSource');
                $source = $incomeSource instanceof IncomeSource
                    ? ($incomeSource->code ?? $incomeSource->name)
                    : null;

                return [
                    'id' => $record->id,
                    'source' => $source,
                    'monthly_amount' => (float) $record->monthly_amount,
                    'annual_amount' => (float) $record->annual_amount,
                    'reference_year' => $record->reference_year,
                    'is_current' => (bool) $record->is_current,
                ];
            })->values()->all(),
            'housing_unit' => [
                'id' => $allocation->housing_unit_id,
                'code' => $housingUnit->code ?? null,
                'address' => $housingUnit->address ?? null,
                'typology' => $contestHousingUnit->typology ?? $housingUnit->typology ?? null,
                'bedrooms' => $contestHousingUnit->bedrooms ?? $housingUnit->bedrooms ?? null,
                'monthly_rent_reference' => (float) ($contestHousingUnit->monthly_rent ?? $housingUnit->monthly_rent ?? 0),
            ],
            'allocation' => [
                'id' => $allocation->id,
                'status' => $this->enumValue($allocation->status),
                'accepted_at' => $this->dateTime($allocation->accepted_at)?->toIso8601String(),
                'ready_for_contract_at' => $this->dateTime($allocation->ready_for_contract_at)?->toIso8601String(),
            ],
            'rent_rule_set' => [
                'id' => $ruleSet->id,
                'name' => $ruleSet->name,
                'status' => $this->enumValue($ruleSet->status),
                'calculation_method' => $this->enumValue($ruleSet->calculation_method),
                'income_period' => $ruleSet->income_period,
                'income_basis' => $ruleSet->income_basis,
                'effort_rate_percentage' => (float) $ruleSet->effort_rate_percentage,
                'minimum_rent' => $ruleSet->minimum_rent !== null ? (float) $ruleSet->minimum_rent : null,
                'maximum_rent' => $ruleSet->maximum_rent !== null ? (float) $ruleSet->maximum_rent : null,
                'deposit_months' => $ruleSet->deposit_months !== null ? (float) $ruleSet->deposit_months : null,
            ],
        ];
    }

    private function enumValue(mixed $value): string|int|null
    {
        return $value instanceof BackedEnum ? $value->value : null;
    }

    private function dateTime(mixed $value): ?CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value : null;
    }
}
