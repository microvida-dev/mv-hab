<?php

namespace App\Services\Scoring;

use App\Enums\HousingStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\CurrentHousingSituation;
use App\Models\EligibilityCheck;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ScoringDataProvider
{
    /**
     * @return array{
     *     application: Application,
     *     values: array<string, array{applicable: bool, value: mixed, missing: bool}>,
     *     snapshots: array<string, array<string, mixed>>
     * }
     */
    public function forApplication(Application $application): array
    {
        $application->loadMissing([
            'administrativeProcess',
            'latestEligibilityCheck',
            'household.members.incomeRecords',
            'household.incomeRecords',
            'currentHousingSituation',
        ]);

        /** @var Household|null $household */
        $household = $application->getRelationValue('household');
        /** @var EloquentCollection<int, HouseholdMember> $members */
        $members = $household instanceof Household ? $household->members : new EloquentCollection;
        /** @var EloquentCollection<int, IncomeRecord> $incomeRecords */
        $incomeRecords = $household instanceof Household ? $household->incomeRecords : new EloquentCollection;
        /** @var CurrentHousingSituation|null $housing */
        $housing = $application->getRelationValue('currentHousingSituation');
        /** @var EligibilityCheck|null $latestEligibilityCheck */
        $latestEligibilityCheck = $application->getRelationValue('latestEligibilityCheck');
        /** @var AdministrativeProcess|null $administrativeProcess */
        $administrativeProcess = $application->getRelationValue('administrativeProcess');

        $memberCount = $members->count();
        $monthlyIncome = (float) $incomeRecords->sum('monthly_amount');
        $annualIncome = (float) $incomeRecords->sum('annual_amount');
        $monthlyPerCapita = $memberCount > 0 ? round($monthlyIncome / $memberCount, 2) : null;
        $annualPerCapita = $memberCount > 0 ? round($annualIncome / $memberCount, 2) : null;
        $rentBurden = $housing?->effortRate($monthlyIncome);
        $nonDependentAdults = $members->filter(
            fn ($member) => ($member->age() ?? 0) >= 18 && ! $member->is_dependent,
        );
        $qualificationDataMissing = $nonDependentAdults->isEmpty()
            || $nonDependentAdults->contains(fn ($member) => $member->qualification_level === null);
        $qualificationPoints = $qualificationDataMissing
            ? null
            : $nonDependentAdults->sum(fn ($member) => $this->qualificationPoints((int) $member->qualification_level));
        $averageAgeDataMissing = $nonDependentAdults->isEmpty()
            || $nonDependentAdults->contains(fn ($member) => $member->age() === null);
        $averageAge = $averageAgeDataMissing ? null : round((float) $nonDependentAdults->avg(fn (HouseholdMember $member): float => (float) ($member->age() ?? 0)), 2);
        $averageAgePoints = $averageAge === null ? null : $this->averageAgePoints($averageAge);
        $dependentsCount = $members->where('is_dependent', true)->count();
        $dependentsPoints = min($dependentsCount + 1, 5);
        $disabledMembers = $members->filter(
            fn ($member) => $member->is_disabled || $member->has_multiple_disabilities,
        );
        $disabilityPoints = $disabledMembers->isEmpty()
            ? 1
            : $disabledMembers->sum(
                fn ($member) => $member->has_multiple_disabilities ? 3 : 2,
            );

        $values = [
            'household_size' => $this->value($household !== null, $memberCount, $household === null),
            'number_of_dependents' => $this->value($household !== null, $members->where('is_dependent', true)->count(), $household === null),
            'number_of_minors' => $this->value($household !== null, $members->filter(fn ($member) => ($member->age() ?? 18) < 18)->count(), $household === null),
            'number_of_disabled_members' => $this->value($household !== null, $members->where('is_disabled', true)->count(), $household === null),
            'qualification_classification_points' => $this->value(
                $household !== null,
                $qualificationPoints,
                $qualificationDataMissing,
            ),
            'average_non_dependent_age' => $this->value(
                $household !== null,
                $averageAge,
                $averageAgeDataMissing,
            ),
            'average_age_classification_points' => $this->value(
                $household !== null,
                $averageAgePoints,
                $averageAgeDataMissing,
            ),
            'dependents_classification_points' => $this->value(
                $household !== null,
                $dependentsPoints,
                $household === null,
            ),
            'disability_classification_points' => $this->value(
                $household !== null,
                $disabilityPoints,
                $household === null,
            ),
            'monthly_household_income' => $this->value($household !== null, $monthlyIncome, $household === null || $incomeRecords->isEmpty()),
            'annual_household_income' => $this->value($household !== null, $annualIncome, $household === null || $incomeRecords->isEmpty()),
            'monthly_income_per_capita' => $this->value($household !== null, $monthlyPerCapita, $household === null || $memberCount === 0 || $incomeRecords->isEmpty()),
            'annual_income_per_capita' => $this->value($household !== null, $annualPerCapita, $household === null || $memberCount === 0 || $incomeRecords->isEmpty()),
            'current_rent_burden' => $this->value($housing !== null, $rentBurden, $housing === null || $rentBurden === null),
            'resides_in_municipality' => $this->value($housing !== null, $housing?->resides_in_municipality, $housing === null),
            'works_in_municipality' => $this->value($housing !== null, $housing?->works_in_municipality, $housing === null),
            'years_residing_in_municipality' => $this->value($housing !== null, $housing?->residence_years_in_municipality === null ? null : (float) $housing->residence_years_in_municipality, $housing === null || $housing->residence_years_in_municipality === null),
            'housing_status' => $this->value($housing !== null, $this->enumValue($housing?->housing_status), $housing === null),
            'housing_condition' => $this->value($housing !== null, $this->enumValue($housing?->current_housing_condition), $housing === null || $housing->current_housing_condition === null),
            'risk_of_eviction' => $this->value($housing !== null, $housing?->is_at_risk_of_eviction, $housing === null),
            'homelessness' => $this->value($housing !== null, $housing?->is_homeless || $this->housingStatus($housing?->housing_status) === HousingStatus::Homeless, $housing === null),
            'temporary_accommodation' => $this->value($housing !== null, $housing?->is_temporary_accommodation, $housing === null),
            'accessibility_needs' => $this->value($housing !== null, $housing?->has_accessibility_needs, $housing === null),
            'submitted_at' => $this->value($this->dateTime($application->submitted_at) !== null, $this->dateTime($application->submitted_at)?->timestamp, $this->dateTime($application->submitted_at) === null),
            'eligibility_result' => $this->value($latestEligibilityCheck !== null, $this->enumValue($latestEligibilityCheck?->result), $latestEligibilityCheck === null),
            'administrative_status' => $this->value($administrativeProcess !== null, $this->enumValue($administrativeProcess?->status), $administrativeProcess === null),
        ];

        return [
            'application' => $application,
            'values' => $values,
            'snapshots' => [
                'application' => [
                    'application_number' => $application->application_number,
                    'status' => $this->enumValue($application->status),
                    'submitted_at' => $this->dateTime($application->submitted_at)?->toIso8601String(),
                ],
                'administrative_process' => [
                    'status' => $this->enumValue($administrativeProcess?->status),
                ],
                'eligibility' => [
                    'latest_result' => $this->enumValue($latestEligibilityCheck?->result),
                    'latest_check_id' => $latestEligibilityCheck?->id,
                ],
                'household' => [
                    'members_count' => $memberCount,
                    'dependents_count' => $members->where('is_dependent', true)->count(),
                    'minors_count' => $members->filter(fn ($member) => ($member->age() ?? 18) < 18)->count(),
                    'disabled_members_count' => $members->where('is_disabled', true)->count(),
                    'qualification_points' => $qualificationPoints,
                    'average_non_dependent_age' => $averageAge,
                    'average_age_points' => $averageAgePoints,
                    'dependents_points' => $dependentsPoints,
                    'disability_points' => $disabilityPoints,
                ],
                'income' => [
                    'monthly_total' => $monthlyIncome,
                    'annual_total' => $annualIncome,
                    'monthly_per_capita' => $monthlyPerCapita,
                    'annual_per_capita' => $annualPerCapita,
                ],
                'housing' => [
                    'status' => $this->enumValue($housing?->housing_status),
                    'condition' => $this->enumValue($housing?->current_housing_condition),
                    'rent_burden' => $rentBurden,
                    'resides_in_municipality' => $housing?->resides_in_municipality,
                    'works_in_municipality' => $housing?->works_in_municipality,
                    'risk_of_eviction' => $housing?->is_at_risk_of_eviction,
                    'homelessness' => $housing?->is_homeless,
                    'temporary_accommodation' => $housing?->is_temporary_accommodation,
                    'accessibility_needs' => $housing?->has_accessibility_needs,
                ],
            ],
        ];
    }

    public function valueFor(Application $application, string $code): mixed
    {
        return $this->forApplication($application)['values'][$code]['value'] ?? null;
    }

    /**
     * @return array{applicable: bool, value: mixed, missing: bool}
     */
    private function value(bool $applicable, mixed $value, bool $missing): array
    {
        return compact('applicable', 'value', 'missing');
    }

    private function enumValue(mixed $value): string|int|null
    {
        return $value instanceof BackedEnum ? $value->value : null;
    }

    private function housingStatus(mixed $value): ?HousingStatus
    {
        return $value instanceof HousingStatus ? $value : null;
    }

    private function dateTime(mixed $value): ?CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value : null;
    }

    private function qualificationPoints(int $level): int
    {
        return match (true) {
            $level <= 4 => 1,
            $level === 5 => 2,
            $level === 6 => 3,
            $level === 7 => 4,
            default => 5,
        };
    }

    private function averageAgePoints(float $averageAge): int
    {
        return match (true) {
            $averageAge > 65 => 1,
            $averageAge >= 56 => 2,
            $averageAge >= 41 => 3,
            $averageAge >= 31 => 4,
            default => 5,
        };
    }
}
