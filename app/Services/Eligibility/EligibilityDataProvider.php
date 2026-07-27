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
use App\Models\EligibilityCriterion;
use App\Models\EligibilityRuleSet;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\Program;
use App\Models\User;
use App\Services\Allocation\TypologyAdequacyService;
use App\Services\Documents\DocumentChecklistService;
use App\Services\Regulatory\MunicipalRegulatoryOverlayService;
use App\Support\DecimalMoney;
use BackedEnum;
use Carbon\CarbonImmutable;
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
    public function __construct(
        private readonly DocumentChecklistService $documentChecklistService,
        private readonly TypologyAdequacyService $typologyAdequacyService,
        private readonly MunicipalRegulatoryOverlayService $overlayService,
        private readonly EligibilityRuleSetResolver $ruleSetResolver,
    ) {}

    /**
     * @return EligibilityContext
     */
    public function forCandidate(
        User $user,
        ?Program $program = null,
        ?Contest $contest = null,
        ?Application $application = null,
        ?EligibilityRuleSet $ruleSet = null,
        ?CarbonInterface $referenceDate = null,
    ): array {
        $referenceDate = $referenceDate === null
            ? CarbonImmutable::now('Europe/Lisbon')
            : CarbonImmutable::instance($referenceDate)->setTimezone('Europe/Lisbon');
        $contest?->loadMissing('program');
        $program ??= $contest?->program;
        $ruleSet ??= $this->ruleSetResolver->resolveAt($referenceDate, $program, $contest);
        $ruleSet?->loadMissing(['criteria', 'regulatoryProfile.parentProfile']);
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
        $monthlyIncome = DecimalMoney::sum($incomeRecords->pluck('monthly_amount'));
        $annualIncome = DecimalMoney::sum($incomeRecords->pluck('annual_amount'));
        $regulatoryParameters = $this->regulatoryParameters($ruleSet);
        $minimumAdultMonthlyIncome = $regulatoryParameters['minimum_adult_monthly_income'];
        $maximumEffortRate = $regulatoryParameters['maximum_effort_rate_percentage'];
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
            : $members->every(fn (HouseholdMember $member) => $this->hasValidNationalityOrResidencePermit($member, $referenceDate));
        $adultIncomeDataMissing = $minimumAdultMonthlyIncome === null
            || $nonDependentAdults->isEmpty()
            || $nonDependentAdults->contains(
                fn (HouseholdMember $member) => ! $member->has_no_income && $member->incomeRecords->isEmpty(),
            );
        $allAdultsMeetRmmg = $adultIncomeDataMissing
            ? null
            : $nonDependentAdults->every(
                fn (HouseholdMember $member) => DecimalMoney::compare(
                    $this->monthlyIncomeFor($member),
                    $minimumAdultMonthlyIncome,
                ) >= 0,
            );
        $annualIncomeLimit = $memberCount > 0
            ? $this->annualIncomeLimit($memberCount, $regulatoryParameters)
            : null;
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
        $rentEffortDataMissing = $application !== null && (
            $selectedUnits->isEmpty()
            || ! DecimalMoney::isPositive($monthlyIncome)
            || $maximumEffortRate === null
        );
        $rentEffortWithinLimit = $rentEffortDataMissing
            ? null
            : $selectedUnits->every(function (ContestHousingUnit $unit) use ($monthlyIncome, $maximumEffortRate): bool {
                $rent = DecimalMoney::normalize((string) ($unit->monthly_rent ?? $unit->housingUnit->monthly_rent ?? 0));
                $effortRate = DecimalMoney::ratioPercentage($rent, $monthlyIncome);

                return DecimalMoney::isPositive($rent)
                    && $effortRate !== null
                    && DecimalMoney::compare($effortRate, $maximumEffortRate, 4) <= 0;
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
                $incomeComplete && $annualIncomeLimit !== null
                    ? DecimalMoney::compare($annualIncome, $annualIncomeLimit) <= 0
                    : null,
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
                    'monthly_total' => (float) $monthlyIncome,
                    'annual_total' => (float) $annualIncome,
                    'monthly_per_capita' => $memberCount > 0 ? (float) DecimalMoney::divide($monthlyIncome, $memberCount) : null,
                    'annual_per_capita' => $memberCount > 0 ? (float) DecimalMoney::divide($annualIncome, $memberCount) : null,
                    'alcanena_annual_income_limit' => $annualIncomeLimit !== null ? (float) $annualIncomeLimit : null,
                    'rmmg_2026' => $minimumAdultMonthlyIncome !== null ? (float) $minimumAdultMonthlyIncome : null,
                    'all_non_dependent_adults_meet_rmmg' => $allAdultsMeetRmmg,
                ],
                'current_housing_situation' => [
                    'exists' => $housing !== null,
                    'status' => $this->enumValue($housing?->housing_status),
                    'resides_in_municipality' => $housing?->resides_in_municipality,
                    'works_in_municipality' => $housing?->works_in_municipality,
                    'effort_rate' => $housing?->effortRate((float) $monthlyIncome),
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
                'regulatory' => [
                    'reference_date' => $referenceDate->toIso8601String(),
                    'regulatory_profile_id' => $ruleSet?->regulatory_profile_id,
                    'parameters' => $regulatoryParameters,
                ],
                'calculated_values' => [
                    'members_count' => $memberCount,
                    'adults_count' => $adultCount,
                    'minors_count' => $minorCount,
                    'dependents_count' => $dependentCount,
                    'monthly_income_total' => (float) $monthlyIncome,
                    'annual_income_total' => (float) $annualIncome,
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

    private function hasValidNationalityOrResidencePermit(
        HouseholdMember $member,
        CarbonInterface $referenceDate,
    ): bool {
        if ($this->isPortuguese($member->nationality)) {
            return true;
        }

        $validUntil = $this->dateTime($member->document_valid_until);

        return Str::contains(Str::lower((string) $member->document_type), ['resid', 'permanencia'])
            && $validUntil?->gte($referenceDate) === true;
    }

    private function monthlyIncomeFor(HouseholdMember $member): string
    {
        return DecimalMoney::sum($member->incomeRecords->pluck('monthly_amount'));
    }

    /**
     * @param  array{
     *     maximum_effort_rate_percentage: string|null,
     *     minimum_adult_monthly_income: string|null,
     *     annual_income_base_limit: string|null,
     *     second_person_increment: string|null,
     *     additional_person_increment: string|null
     * }  $parameters
     */
    private function annualIncomeLimit(int $memberCount, array $parameters): ?string
    {
        $base = $parameters['annual_income_base_limit'];
        $secondPerson = $parameters['second_person_increment'];
        $additionalPerson = $parameters['additional_person_increment'];

        if ($base === null) {
            return null;
        }

        if ($memberCount <= 1) {
            return DecimalMoney::normalize($base);
        }

        if ($secondPerson === null) {
            return null;
        }

        $limit = DecimalMoney::add($base, $secondPerson);

        if ($memberCount === 2) {
            return $limit;
        }

        return $additionalPerson === null
            ? null
            : DecimalMoney::add(
                $limit,
                DecimalMoney::multiply($additionalPerson, $memberCount - 2),
            );
    }

    /**
     * @return array{
     *     maximum_effort_rate_percentage: string|null,
     *     minimum_adult_monthly_income: string|null,
     *     annual_income_base_limit: string|null,
     *     second_person_increment: string|null,
     *     additional_person_increment: string|null
     * }
     */
    private function regulatoryParameters(?EligibilityRuleSet $ruleSet): array
    {
        if ($ruleSet === null) {
            return [
                'maximum_effort_rate_percentage' => null,
                'minimum_adult_monthly_income' => null,
                'annual_income_base_limit' => null,
                'second_person_increment' => null,
                'additional_person_increment' => null,
            ];
        }

        $criteria = $ruleSet->criteria;
        $profile = $ruleSet->regulatoryProfile;
        $effective = $profile === null ? [] : $this->overlayService->effectiveParameters($profile);
        $incomeLimit = $this->criterionExpectedValue($criteria->firstWhere(
            'code',
            'annual_income_within_alcanena_limit',
        ));
        $minimumIncome = $this->criterionExpectedValue($criteria->firstWhere(
            'code',
            'all_non_dependent_adults_meet_rmmg',
        ));
        $effortRate = $this->criterionExpectedValue($criteria->firstWhere(
            'code',
            'rent_effort_within_35_percent',
        ));

        return [
            'maximum_effort_rate_percentage' => $this->decimalParameter(
                $effective['maximum_effort_rate_percentage'] ?? $effortRate['maximum_percentage'] ?? null,
            ),
            'minimum_adult_monthly_income' => $this->decimalParameter(
                $effective['minimum_adult_monthly_income'] ?? $minimumIncome['monthly_minimum'] ?? null,
            ),
            'annual_income_base_limit' => $this->decimalParameter(
                $effective['annual_income_base_limit'] ?? $incomeLimit['base_one_person'] ?? null,
            ),
            'second_person_increment' => $this->decimalParameter(
                $effective['second_person_increment'] ?? $incomeLimit['second_person_increment'] ?? null,
            ),
            'additional_person_increment' => $this->decimalParameter(
                $effective['additional_person_increment'] ?? $incomeLimit['additional_person_increment'] ?? null,
            ),
        ];
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    private function criterionExpectedValue(mixed $criterion): array
    {
        return $criterion instanceof EligibilityCriterion
            ? ($criterion->expected_value ?? [])
            : [];
    }

    private function decimalParameter(mixed $value): ?string
    {
        return is_numeric($value) ? DecimalMoney::normalize((string) $value) : null;
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

        $preferenceOrder = $application->preferences
            ->pluck('preference_order', 'housing_unit_id');

        return ContestHousingUnit::query()
            ->with('housingUnit')
            ->where('contest_id', $application->contest_id)
            ->whereIn('housing_unit_id', $preferenceOrder->keys())
            ->get()
            ->sortBy(fn (ContestHousingUnit $unit) => $preferenceOrder->get($unit->housing_unit_id, PHP_INT_MAX))
            ->values();
    }
}
