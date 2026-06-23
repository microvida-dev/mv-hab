<?php

namespace App\Services\Eligibility;

use App\Enums\AdhesionRegistrationStatus;
use App\Enums\ApplicationStatus;
use App\Enums\TypologyAdequacyResult;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\Program;
use App\Models\User;
use App\Services\Allocation\TypologyAdequacyService;
use App\Services\Documents\DocumentChecklistService;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @phpstan-type EligibilityScalar bool|float|int|string|null
 * @phpstan-type EligibilitySource array{applicable: bool, value: EligibilityScalar, missing: bool}
 * @phpstan-type EligibilityContext array{user: User, program: Program|null, contest: Contest|null, application: Application|null, registration: AdhesionRegistration|null, values: array<string, EligibilitySource>, missing_data: array<int, string>, warnings: array<int, string>, snapshots: array<string, mixed>}
 */
class EligibilityDataProvider
{
    public const ALCANENA_RMMG_2026 = 920.00;

    public const ALCANENA_MAX_EFFORT_RATE = 35.00;

    public function __construct(
        private readonly DocumentChecklistService $documentChecklistService,
        private readonly TypologyAdequacyService $typologyAdequacyService,
    ) {}

    /**
     * @return EligibilityContext
     */
    public function forCandidate(
        User $user,
        ?Program $program = null,
        ?Contest $contest = null,
        ?Application $application = null,
    ): array {
        $contest?->loadMissing('program');
        $program ??= $contest?->program;
        $application?->loadMissing([
            'housingPreferences.contestHousingUnit.housingUnit',
            'preferences.housingUnit',
        ]);

        $registration = $application instanceof Application
            ? $application->adhesionRegistration
            : $user->adhesionRegistration()->first();

        $registration?->loadMissing([
            'household.members.incomeRecords',
            'household.incomeRecords',
            'currentHousingSituation',
            'documentSubmissions',
        ]);

        /** @var Household|null $household */
        $household = $application instanceof Application ? $application->household : $registration?->household;
        /** @var EloquentCollection<int, HouseholdMember> $members */
        $members = $household instanceof Household ? $household->members : new EloquentCollection;
        /** @var CurrentHousingSituation|null $housing */
        $housing = $application instanceof Application ? $application->currentHousingSituation : $registration?->currentHousingSituation;
        /** @var EloquentCollection<int, IncomeRecord> $incomeRecords */
        $incomeRecords = $household instanceof Household ? $household->incomeRecords : new EloquentCollection;
        $memberCount = $members->count();
        $adultCount = $members->filter(fn ($member) => ($member->age() ?? 0) >= 18)->count();
        $minorCount = $members->filter(fn ($member) => ($member->age() ?? 18) < 18)->count();
        $dependentCount = $members->where('is_dependent', true)->count();
        $monthlyIncome = (float) $incomeRecords->sum('monthly_amount');
        $annualIncome = (float) $incomeRecords->sum('annual_amount');
        $incomeComplete = $members->isNotEmpty()
            && $members->every(fn ($member) => $member->has_no_income || $member->incomeRecords->isNotEmpty());
        $nonDependentAdults = $members->filter(
            fn (HouseholdMember $member) => ($member->age() ?? 0) >= 18 && ! $member->is_dependent,
        );
        $residencyDataMissing = $members->isEmpty()
            || $members->contains(fn (HouseholdMember $member) => blank($member->nationality)
                || (! $this->isPortuguese($member->nationality) && (
                    blank($member->document_type)
                    || $member->document_valid_until === null
                )));
        $allMembersHaveValidResidency = $residencyDataMissing
            ? null
            : $members->every(fn (HouseholdMember $member) => $this->hasValidNationalityOrResidencePermit($member));
        $adultIncomeDataMissing = $nonDependentAdults->isEmpty()
            || $nonDependentAdults->contains(
                fn (HouseholdMember $member) => ! $member->has_no_income && $member->incomeRecords->isEmpty(),
            );
        $allAdultsMeetRmmg = $adultIncomeDataMissing
            ? null
            : $nonDependentAdults->every(
                fn (HouseholdMember $member) => $this->monthlyIncomeFor($member) >= self::ALCANENA_RMMG_2026,
            );
        $annualIncomeLimit = $memberCount > 0 ? $this->alcanenaAnnualIncomeLimit($memberCount) : null;
        $selectedUnits = $this->selectedContestUnits($application);
        $typologyResults = $application instanceof Application
            ? $selectedUnits->map(
                fn (ContestHousingUnit $unit) => $this->typologyAdequacyService->evaluate($application, $unit),
            )
            : collect();
        $typologyDataMissing = $application !== null && $selectedUnits->isEmpty();
        $typologyIsAdequate = $typologyDataMissing
            ? null
            : $typologyResults->every(fn (TypologyAdequacyResult $result) => $result === TypologyAdequacyResult::Adequate);
        $rentEffortDataMissing = $application !== null && ($selectedUnits->isEmpty() || $monthlyIncome <= 0);
        $rentEffortWithinLimit = $rentEffortDataMissing
            ? null
            : $selectedUnits->every(function (ContestHousingUnit $unit) use ($monthlyIncome): bool {
                $rent = (float) ($unit->monthly_rent ?? $unit->housingUnit->monthly_rent ?? 0);

                return $rent > 0
                    && round(($rent / $monthlyIncome) * 100, 2) <= self::ALCANENA_MAX_EFFORT_RATE;
            });

        $documentChecklist = $registration
            ? ($application
                ? $this->documentChecklistService->forApplication($application)
                : $this->documentChecklistService->forRegistration(
                    $registration,
                    program: $program,
                    contest: $contest,
                ))
            : null;
        $documentSummary = $documentChecklist['summary'] ?? null;

        $duplicateExists = $contest
            ? $this->hasActiveDuplicate($user, $contest, $application)
            : null;
        $specialCondition = $housing && (
            $housing->is_domestic_violence_victim
            || $housing->has_accessibility_needs
            || $housing->is_homeless
            || $housing->is_at_risk_of_eviction
        );

        $values = [
            'registration_is_registered' => $this->value(
                true,
                $registration?->status === AdhesionRegistrationStatus::Registered
                    ? true
                    : ($registration ? false : null),
                $registration === null,
            ),
            'candidate_is_adult' => $this->value(
                true,
                $registration?->isAdult(),
                $registration === null || $registration->birth_date === null,
            ),
            'all_household_members_have_valid_residency' => $this->value(
                $household !== null,
                $allMembersHaveValidResidency,
                $residencyDataMissing,
            ),
            'contest_is_open' => $this->value($contest !== null, $contest?->isOpenForApplications(), false),
            'has_household' => $this->value(
                true,
                $registration ? $household !== null : null,
                $registration === null,
            ),
            'has_applicant_member' => $this->value(
                $household !== null,
                $members->contains('is_applicant', true),
                false,
            ),
            'has_income_information' => $this->value($household !== null, $incomeComplete, ! $incomeComplete),
            'income_above_minimum' => $this->value($household !== null, $incomeComplete ? $annualIncome : null, ! $incomeComplete),
            'income_below_maximum' => $this->value($household !== null, $incomeComplete ? $annualIncome : null, ! $incomeComplete),
            'annual_income_within_alcanena_limit' => $this->value(
                $household !== null,
                $incomeComplete && $annualIncomeLimit !== null ? $annualIncome <= $annualIncomeLimit : null,
                ! $incomeComplete || $annualIncomeLimit === null,
            ),
            'all_non_dependent_adults_meet_rmmg' => $this->value(
                $household !== null,
                $allAdultsMeetRmmg,
                $adultIncomeDataMissing,
            ),
            'has_current_housing_situation' => $this->value(
                true,
                $registration ? $housing !== null : null,
                $registration === null,
            ),
            'resides_in_municipality' => $this->value($housing !== null, $housing?->resides_in_municipality, $housing === null),
            'works_in_municipality' => $this->value($housing !== null, $housing?->works_in_municipality, $housing === null),
            'has_required_documents_submitted' => $this->value(
                $registration !== null,
                $documentSummary
                    ? $documentSummary['missing'] === 0 && $documentSummary['rejected'] === 0
                    : null,
                $registration === null,
            ),
            'has_required_documents_validated' => $this->value(
                $registration !== null,
                $documentSummary
                    ? $documentSummary['validated'] === $documentSummary['total_required']
                    : null,
                $registration === null,
            ),
            'no_duplicate_active_application' => $this->value($contest !== null, $duplicateExists === null ? null : ! $duplicateExists, false),
            'typology_is_adequate' => $this->value(
                $application !== null,
                $typologyIsAdequate,
                $typologyDataMissing,
            ),
            'rent_effort_within_35_percent' => $this->value(
                $application !== null,
                $rentEffortWithinLimit,
                $rentEffortDataMissing,
            ),
            'no_declared_property_impediment' => $this->value(true, null, false),
            'no_incompatible_housing_support' => $this->value(true, null, false),
            'tax_and_social_security_status_regular' => $this->value(true, null, false),
            'no_unregulated_municipal_debt' => $this->value(true, null, false),
            'no_accumulated_public_housing_support' => $this->value(true, null, false),
            'no_fraud_or_false_declarations_last_five_years' => $this->value(true, null, false),
            'no_municipal_eviction_or_breach_last_five_years' => $this->value(true, null, false),
            'requires_manual_review_for_special_conditions' => $this->value((bool) $specialCondition, null, false),
        ];

        $missingData = collect($values)
            ->filter(fn (array $value) => $value['missing'])
            ->keys()
            ->values()
            ->all();

        return [
            'user' => $user,
            'program' => $program,
            'contest' => $contest,
            'application' => $application,
            'registration' => $registration,
            'values' => $values,
            'missing_data' => $missingData,
            'warnings' => $specialCondition
                ? ['Existem condições especiais declaradas que podem exigir análise municipal.']
                : [],
            'snapshots' => [
                'adhesion_registration' => [
                    'exists' => $registration !== null,
                    'status' => $registration?->status?->value,
                    'candidate_is_adult' => $registration?->isAdult(),
                ],
                'household' => [
                    'exists' => $household !== null,
                    'members_count' => $memberCount,
                    'has_applicant_member' => $members->contains('is_applicant', true),
                ],
                'household_members' => [
                    'total' => $memberCount,
                    'adults' => $adultCount,
                    'minors' => $minorCount,
                    'dependents' => $dependentCount,
                    'income_information_complete' => $incomeComplete,
                ],
                'income_records' => [
                    'records_count' => $incomeRecords->count(),
                    'monthly_total' => $monthlyIncome,
                    'annual_total' => $annualIncome,
                    'monthly_per_capita' => $memberCount > 0 ? round($monthlyIncome / $memberCount, 2) : null,
                    'annual_per_capita' => $memberCount > 0 ? round($annualIncome / $memberCount, 2) : null,
                    'alcanena_annual_income_limit' => $annualIncomeLimit,
                    'rmmg_2026' => self::ALCANENA_RMMG_2026,
                    'all_non_dependent_adults_meet_rmmg' => $allAdultsMeetRmmg,
                ],
                'current_housing_situation' => [
                    'exists' => $housing !== null,
                    'status' => $this->enumValue($housing?->housing_status),
                    'resides_in_municipality' => $housing?->resides_in_municipality,
                    'works_in_municipality' => $housing?->works_in_municipality,
                    'effort_rate' => $housing?->effortRate($monthlyIncome),
                    'special_condition_requires_review' => (bool) $specialCondition,
                ],
                'documents' => $documentSummary ?? [
                    'total_required' => 0,
                    'missing' => 0,
                    'submitted' => 0,
                    'validated' => 0,
                    'rejected' => 0,
                ],
                'application' => [
                    'exists' => $application !== null,
                    'status' => $this->enumValue($application?->status),
                    'submitted_at' => $this->dateTime($application?->submitted_at)?->toIso8601String(),
                    'selected_housing_units' => $selectedUnits->pluck('housing_unit_id')->values()->all(),
                    'typology_is_adequate' => $typologyIsAdequate,
                    'rent_effort_within_35_percent' => $rentEffortWithinLimit,
                ],
                'calculated_values' => [
                    'members_count' => $memberCount,
                    'adults_count' => $adultCount,
                    'minors_count' => $minorCount,
                    'dependents_count' => $dependentCount,
                    'monthly_income_total' => $monthlyIncome,
                    'annual_income_total' => $annualIncome,
                    'duplicate_active_application' => $duplicateExists,
                ],
            ],
        ];
    }

    /**
     * @return EligibilitySource
     */
    private function value(bool $applicable, bool|float|int|string|null $value, bool $missing): array
    {
        return compact('applicable', 'value', 'missing');
    }

    private function hasActiveDuplicate(User $user, Contest $contest, ?Application $application): bool
    {
        $activeStatuses = collect(ApplicationStatus::cases())
            ->filter(fn (ApplicationStatus $status) => $status->isActive())
            ->map->value
            ->all();

        $query = Application::query()
            ->forUser($user)
            ->where('contest_id', $contest->id)
            ->whereIn('status', $activeStatuses);

        if ($application instanceof Application) {
            $query->whereKeyNot($application->id);
        }

        return $query->exists();
    }

    private function isPortuguese(?string $nationality): bool
    {
        $normalized = Str::lower(Str::ascii(trim((string) $nationality)));

        return in_array($normalized, ['portugal', 'portugues', 'portuguesa'], true);
    }

    private function hasValidNationalityOrResidencePermit(HouseholdMember $member): bool
    {
        if ($this->isPortuguese($member->nationality)) {
            return true;
        }

        $validUntil = $this->dateTime($member->document_valid_until);

        return Str::contains(Str::lower((string) $member->document_type), ['resid', 'permanencia'])
            && $validUntil?->gte(today()) === true;
    }

    private function monthlyIncomeFor(HouseholdMember $member): float
    {
        return (float) $member->incomeRecords->sum('monthly_amount');
    }

    private function alcanenaAnnualIncomeLimit(int $memberCount): float
    {
        return match (true) {
            $memberCount <= 1 => 38632.00,
            $memberCount === 2 => 48632.00,
            default => 48632.00 + (($memberCount - 2) * 5000.00),
        };
    }

    private function enumValue(mixed $value): string|int|null
    {
        return $value instanceof BackedEnum ? $value->value : null;
    }

    private function dateTime(mixed $value): ?CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value : null;
    }

    /**
     * @return Collection<int, ContestHousingUnit>
     */
    private function selectedContestUnits(?Application $application): Collection
    {
        if (! $application) {
            return collect();
        }

        $currentPreferences = $application->housingPreferences
            ->pluck('contestHousingUnit')
            ->filter();

        if ($currentPreferences->isNotEmpty()) {
            return $currentPreferences->values();
        }

        return $application->preferences
            ->map(fn ($preference) => ContestHousingUnit::query()
                ->with('housingUnit')
                ->where('contest_id', $application->contest_id)
                ->where('housing_unit_id', $preference->housing_unit_id)
                ->first())
            ->filter()
            ->values();
    }
}
