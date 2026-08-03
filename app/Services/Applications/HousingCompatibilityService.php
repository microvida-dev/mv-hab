<?php

namespace App\Services\Applications;

use App\Data\Applications\CompatibleHousingOptionData;
use App\Data\Applications\HousingCompatibilityResult;
use App\Enums\ApplicationStatus;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\ContestStatus;
use App\Enums\HousingCompatibilityStatus;
use App\Enums\HousingPublicStatus;
use App\Enums\HousingUnitStatus;
use App\Enums\PublicVisibilityStatus;
use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\TypologyAdequacyResult;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\AllocationRuleSet;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\RegulatorySnapshot;
use App\Models\TypologyAdequacyRule;
use App\Services\Allocation\AllocationRuleSetResolver;
use App\Services\Allocation\TypologyAdequacyService;
use App\Services\Regulatory\AnnualHouseholdIncomeLimitCalculator;
use App\Services\Regulatory\MunicipalRegulatoryOverlayService;
use App\Support\DecimalMoney;
use App\Support\HousingTypology;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class HousingCompatibilityService
{
    public function __construct(
        private readonly AllocationRuleSetResolver $allocationRules,
        private readonly TypologyAdequacyService $typology,
        private readonly MunicipalRegulatoryOverlayService $regulatoryOverlay,
        private readonly AnnualHouseholdIncomeLimitCalculator $annualIncomeLimits,
    ) {}

    /**
     * @return Collection<int, CompatibleHousingOptionData>
     */
    public function optionsFor(Application $application): Collection
    {
        $context = $this->context($application);
        $municipalityId = $context['municipality_id'];

        if ($municipalityId === null) {
            return collect();
        }

        return ContestHousingUnit::query()
            ->select([
                'id',
                'program_id',
                'contest_id',
                'housing_unit_id',
                'status',
                'availability_starts_at',
                'availability_ends_at',
                'typology',
                'bedrooms',
                'min_occupants',
                'max_occupants',
                'accessible',
                'reserved_for_special_condition',
                'monthly_rent',
                'estimated_expenses',
            ])
            ->available()
            ->where('contest_id', $application->contest_id)
            ->where(function ($query): void {
                $query->whereNull('availability_starts_at')
                    ->orWhere('availability_starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('availability_ends_at')
                    ->orWhere('availability_ends_at', '>=', now());
            })
            ->whereHas('housingUnit', function ($query) use ($municipalityId): void {
                $query
                    ->where('municipality_id', $municipalityId)
                    ->where('status', HousingUnitStatus::Available->value)
                    ->where('is_public', true)
                    ->where('public_status', HousingPublicStatus::Available->value)
                    ->where('public_visibility_status', PublicVisibilityStatus::Published->value)
                    ->whereNotNull('public_slug')
                    ->where(function ($visibility): void {
                        $visibility->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    })
                    ->whereNull('unpublished_at');
            })
            ->with([
                'housingUnit' => fn ($query) => $query->select([
                    'id',
                    'municipality_id',
                    'code',
                    'typology',
                    'bedrooms',
                    'monthly_rent',
                    'status',
                    'public_reference',
                    'public_title',
                    'public_slug',
                    'public_summary',
                    'parish',
                    'locality',
                    'public_location_description',
                    'gross_area_sqm',
                    'usable_area_sqm',
                    'energy_rating',
                    'public_status',
                    'public_visibility_status',
                    'is_public',
                    'published_at',
                    'unpublished_at',
                ]),
                'housingUnit.coverImage',
                'housingUnit.publicFeatures',
            ])
            ->orderBy('typology')
            ->orderBy('monthly_rent')
            ->orderBy('id')
            ->get()
            ->map(fn (ContestHousingUnit $unit): CompatibleHousingOptionData => new CompatibleHousingOptionData(
                $unit,
                $this->evaluateWithContext($application, $unit, $context),
            ))
            ->filter(fn (CompatibleHousingOptionData $option): bool => $option->compatibility->compatible)
            ->values();
    }

    public function evaluate(
        Application $application,
        ContestHousingUnit $unit,
    ): HousingCompatibilityResult {
        return $this->evaluateWithContext(
            $application,
            $unit->loadMissing('housingUnit'),
            $this->context($application),
        );
    }

    public function assertCompatible(
        Application $application,
        ContestHousingUnit $unit,
    ): void {
        $result = $this->evaluate($application, $unit);

        if ($result->compatible) {
            return;
        }

        $messages = collect($result->checks)
            ->where('passed', false)
            ->pluck('message')
            ->filter()
            ->values()
            ->all();

        throw ValidationException::withMessages([
            'preferences' => $messages !== []
                ? $messages
                : ['A habitação selecionada não é compatível com a candidatura.'],
        ]);
    }

    /**
     * @return array{
     *     household_members: int,
     *     income_complete: bool,
     *     annual_income: string|null,
     *     annual_income_limit: string|null,
     *     annual_income_limit_evidence: array<string, mixed>,
     *     monthly_income: string|null,
     *     maximum_monthly_rent: string|null,
     *     maximum_effort_rate_percentage: string|null,
     *     adequate_typologies: list<string>,
     *     regulatory_regime: string|null,
     *     regulatory_profile: string|null,
     *     configuration_complete: bool
     * }
     */
    public function summaryFor(Application $application): array
    {
        $context = $this->context($application);
        $members = $this->membersFor($application);
        $memberCount = $members->count();
        $incomeComplete = $this->incomeIsComplete($members);
        $annualIncome = $incomeComplete
            ? $this->annualIncome($application)
            : null;
        $monthlyIncome = $incomeComplete
            ? $this->monthlyIncome($application)
            : null;
        $annualLimitResult = $this->annualIncomeLimits->calculate(
            $memberCount,
            $context['regulatory_parameters'],
            $context['reference_date'],
        );
        $annualLimit = $annualLimitResult->effectiveLimit;
        $maximumEffortRate = $this->decimal(
            $context['regulatory_parameters']['maximum_effort_rate_percentage']
                ?? null,
        );
        $profile = $context['profile'];
        $snapshot = $context['regulatory_snapshot'];
        $ruleSet = $context['rule_set'];
        $typologies = $context['typology_rules']
            ->filter(fn (TypologyAdequacyRule $rule): bool => (
                $rule->min_household_members === null
                    || $memberCount >= (int) $rule->min_household_members
            ) && (
                $rule->max_household_members === null
                    || $memberCount <= (int) $rule->max_household_members
            ))
            ->map(fn (TypologyAdequacyRule $rule): ?string => $rule->typology)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $typologies = array_values($typologies);

        return [
            'household_members' => $memberCount,
            'income_complete' => $incomeComplete,
            'annual_income' => $annualIncome,
            'annual_income_limit' => $annualLimit,
            'annual_income_limit_evidence' => $annualLimitResult->toArray(),
            'monthly_income' => $monthlyIncome,
            'maximum_monthly_rent' => $monthlyIncome !== null
                && $maximumEffortRate !== null
                    ? DecimalMoney::percentage(
                        $monthlyIncome,
                        $maximumEffortRate,
                    )
                    : null,
            'maximum_effort_rate_percentage' => $maximumEffortRate,
            'adequate_typologies' => $typologies,
            'regulatory_regime' => $profile?->legal_regime->label(),
            'regulatory_profile' => $profile?->name,
            'configuration_complete' => $profile instanceof AffordableRentRegulatoryProfile
                && $profile->configuration_status === RegulatoryConfigurationStatus::Complete
                && $snapshot instanceof RegulatorySnapshot
                && $ruleSet instanceof AllocationRuleSet
                && $ruleSet->allow_preferences
                && $ruleSet->regulatory_profile_id === $profile->id
                && $context['typology_rules']->isNotEmpty()
                && $annualLimitResult->isConfigured()
                && $maximumEffortRate !== null,
        ];
    }

    /**
     * @return array{
     *     rule_set: AllocationRuleSet|null,
     *     profile: AffordableRentRegulatoryProfile|null,
     *     regulatory_snapshot: RegulatorySnapshot|null,
     *     regulatory_parameters: array<string, mixed>,
     *     typology_rules: EloquentCollection<int, TypologyAdequacyRule>,
     *     municipality_id: int|null,
     *     reference_date: Carbon
     * }
     */
    private function context(Application $application): array
    {
        $application->loadMissing([
            'contest.program.regulatoryProfile.parentProfile',
            'contest.regulatoryProfile.parentProfile',
            'contest.regulatorySnapshot.profile.parentProfile',
            'program.regulatoryProfile.parentProfile',
            'program.regulatorySnapshot.profile.parentProfile',
            'regulatorySnapshot.profile.parentProfile',
            'household.members.incomeRecords',
            'household.incomeRecords',
            'currentHousingSituation',
        ]);

        $ruleSet = $this->allocationRules->forApplication($application);
        $applicationSnapshot = $application->getRelationValue(
            'regulatorySnapshot',
        );
        $contestSnapshot = $application->contest->getRelationValue(
            'regulatorySnapshot',
        );
        $programSnapshot = $application->program->getRelationValue(
            'regulatorySnapshot',
        );
        $regulatorySnapshot = match (true) {
            $applicationSnapshot instanceof RegulatorySnapshot => $applicationSnapshot,
            $contestSnapshot instanceof RegulatorySnapshot => $contestSnapshot,
            $programSnapshot instanceof RegulatorySnapshot => $programSnapshot,
            default => null,
        };
        $snapshotProfile = $regulatorySnapshot?->getRelationValue('profile');
        $contestProfile = $application->contest->getRelationValue(
            'regulatoryProfile',
        );
        $programProfile = $application->program->getRelationValue(
            'regulatoryProfile',
        );
        $profile = match (true) {
            $snapshotProfile instanceof AffordableRentRegulatoryProfile => $snapshotProfile,
            $contestProfile instanceof AffordableRentRegulatoryProfile => $contestProfile,
            $programProfile instanceof AffordableRentRegulatoryProfile => $programProfile,
            default => null,
        };
        $parameters = $regulatorySnapshot instanceof RegulatorySnapshot
            ? ($regulatorySnapshot->parameters ?? [])
            : ($profile instanceof AffordableRentRegulatoryProfile
                ? $this->regulatoryOverlay->effectiveParameters($profile)
                : []);
        $prototype = new ContestHousingUnit([
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
        ]);
        $typologyRules = $this->typology->rulesFor(
            $prototype,
            $profile?->id,
        );

        return [
            'rule_set' => $ruleSet,
            'profile' => $profile,
            'regulatory_snapshot' => $regulatorySnapshot,
            'regulatory_parameters' => $parameters,
            'typology_rules' => $typologyRules,
            'municipality_id' => $application->program->municipality_id,
            'reference_date' => now(),
        ];
    }

    /**
     * @param  array{
     *     rule_set: AllocationRuleSet|null,
     *     profile: AffordableRentRegulatoryProfile|null,
     *     regulatory_snapshot: RegulatorySnapshot|null,
     *     regulatory_parameters: array<string, mixed>,
     *     typology_rules: EloquentCollection<int, TypologyAdequacyRule>,
     *     municipality_id: int|null,
     *     reference_date: Carbon
     * }  $context
     */
    private function evaluateWithContext(
        Application $application,
        ContestHousingUnit $unit,
        array $context,
    ): HousingCompatibilityResult {
        $ruleSet = $context['rule_set'];
        $profile = $context['profile'];
        $regulatorySnapshot = $context['regulatory_snapshot'];
        $parameters = $context['regulatory_parameters'];
        $referenceDate = $context['reference_date'];
        $contest = $application->contest;
        $housingUnit = $unit->housingUnit;
        $members = $this->membersFor($application);
        $memberCount = $members->count();
        $incomeComplete = $this->incomeIsComplete($members);
        $monthlyIncome = $this->monthlyIncome($application);
        $annualIncome = $this->annualIncome($application);
        $annualLimitResult = $this->annualIncomeLimits->calculate(
            $memberCount,
            $parameters,
            $referenceDate,
        );
        $annualLimit = $annualLimitResult->effectiveLimit;
        $rent = $this->decimal($unit->monthly_rent ?? $housingUnit->monthly_rent);
        $maximumEffortRate = $this->decimal(
            $parameters['maximum_effort_rate_percentage'] ?? null,
        );
        $effortRate = $monthlyIncome !== null && DecimalMoney::isPositive($monthlyIncome)
            && $rent !== null
            ? DecimalMoney::ratioPercentage($rent, $monthlyIncome)
            : null;
        $requiresAccessibility = $members->contains(
            fn (HouseholdMember $member): bool => (bool) $member->has_reduced_mobility
                || (bool) $member->is_disabled,
        ) || (bool) $application->currentHousingSituation?->has_accessibility_needs;
        $typologyCode = HousingTypology::from(
            $unit->typology ?: $housingUnit->typology,
        );
        $typologyResult = $this->typology->evaluateWithRules(
            $application,
            $unit,
            $context['typology_rules'],
        );
        $specialCondition = $this->specialConditionMatch(
            $unit->reserved_for_special_condition,
            $members,
            $application,
        );

        $configurationComplete = $profile instanceof AffordableRentRegulatoryProfile
            && $profile->configuration_status === RegulatoryConfigurationStatus::Complete
            && $regulatorySnapshot instanceof RegulatorySnapshot
            && $ruleSet instanceof AllocationRuleSet
            && $ruleSet->allow_preferences
            && $ruleSet->regulatory_profile_id === $profile->id
            && $annualLimitResult->isConfigured()
            && $maximumEffortRate !== null
            && $context['typology_rules']->isNotEmpty();
        $contestOpen = $contest->status === ContestStatus::Published
            && ($contest->published_at === null
                || $contest->published_at->lte($referenceDate))
            && ($contest->opens_at === null
                || $contest->opens_at->lte($referenceDate))
            && ($contest->closes_at === null
                || $contest->closes_at->gte($referenceDate));
        $windowOpen = $ruleSet instanceof AllocationRuleSet
            && ($ruleSet->preference_selection_starts_at === null
                || $ruleSet->preference_selection_starts_at->lte($referenceDate))
            && ($ruleSet->preference_selection_ends_at === null
                || $ruleSet->preference_selection_ends_at->gte($referenceDate));
        $availabilityOpen = ($unit->availability_starts_at === null
                || $unit->availability_starts_at->lte($referenceDate))
            && ($unit->availability_ends_at === null
                || $unit->availability_ends_at->gte($referenceDate));
        $unitAvailable = $unit->status === ContestHousingUnitStatus::Available
            && $housingUnit->status === HousingUnitStatus::Available
            && $availabilityOpen;
        $publiclySelectable = $housingUnit->is_public === true
            && $housingUnit->public_status === HousingPublicStatus::Available
            && $housingUnit->public_visibility_status === PublicVisibilityStatus::Published
            && is_string($housingUnit->public_slug)
            && $housingUnit->public_slug !== ''
            && ($housingUnit->published_at === null
                || $housingUnit->published_at->lte($referenceDate))
            && $housingUnit->unpublished_at === null;
        $sameMunicipality = $context['municipality_id'] !== null
            && $housingUnit->municipality_id === $context['municipality_id'];
        $capacityValid = $memberCount > 0
            && ($unit->min_occupants === null || $memberCount >= (int) $unit->min_occupants)
            && ($unit->max_occupants === null || $memberCount <= (int) $unit->max_occupants);
        $accessibilityValid = ! $requiresAccessibility || (bool) $unit->accessible;
        $specialConditionValid = $unit->reserved_for_special_condition === null
            || $specialCondition === true;
        $incomeLimitValid = $incomeComplete
            && $annualIncome !== null
            && $annualLimit !== null
            && DecimalMoney::compare($annualIncome, $annualLimit) <= 0;
        $minimumIncomeValid = $incomeComplete
            && $this->adultsMeetMinimumIncome(
                $members,
                $this->decimal($parameters['minimum_adult_monthly_income'] ?? null),
            );
        $effortValid = $incomeComplete
            && $effortRate !== null
            && $maximumEffortRate !== null
            && DecimalMoney::compare($effortRate, $maximumEffortRate, 4) <= 0;
        $typologyValid = $typologyCode instanceof HousingTypology
            && $typologyResult === TypologyAdequacyResult::Adequate
            && $capacityValid;

        $checks = [
            $this->check('configuration', 'Configuração regulamentar', $configurationComplete, 'A configuração regulamentar deste concurso não está completa.'),
            $this->check('draft', 'Estado da candidatura', $application->status === ApplicationStatus::Draft, 'A candidatura já não permite alterar habitações pretendidas.'),
            $this->check('contest', 'Concurso', $unit->contest_id === $application->contest_id, 'A habitação não pertence ao concurso desta candidatura.'),
            $this->check('contest_window', 'Prazo do concurso', $contestOpen, 'O concurso não está aberto para seleção de habitações.'),
            $this->check('selection_window', 'Período de seleção', $windowOpen, 'O período de seleção de habitações não está aberto.'),
            $this->check('municipality', 'Município', $sameMunicipality, 'A habitação pertence a outro Município.'),
            $this->check('availability', 'Disponibilidade', $unitAvailable, 'A habitação deixou de estar disponível.'),
            $this->check('visibility', 'Publicação', $publiclySelectable, 'A habitação não está publicada para seleção.'),
            $this->check('household', 'Agregado', $memberCount > 0, 'Complete o agregado antes de escolher habitações.'),
            $this->check('income_data', 'Rendimentos', $incomeComplete, 'Complete os rendimentos de todos os membros do agregado.'),
            $this->check('income_limit', 'Limite anual', $incomeLimitValid, 'O rendimento anual excede o limite aplicável ou não pode ser validado.'),
            $this->check('minimum_income', 'Rendimento mínimo', $minimumIncomeValid, 'O rendimento mínimo aplicável não está cumprido.'),
            $this->check('effort_rate', 'Taxa de esforço', $effortValid, 'A renda ultrapassa a taxa de esforço máxima aplicável.'),
            $this->check('typology', 'Tipologia e ocupação', $typologyValid, 'A tipologia ou a capacidade da habitação não é adequada ao agregado.'),
            $this->check('accessibility', 'Acessibilidade', $accessibilityValid, 'A habitação não responde às necessidades de acessibilidade declaradas.'),
            $this->check('special_condition', 'Condição especial', $specialConditionValid, 'A condição especial reservada para esta habitação não está demonstrada.'),
        ];

        $status = match (true) {
            ! $configurationComplete => HousingCompatibilityStatus::ConfigurationIncomplete,
            $memberCount === 0 || ! $incomeComplete => HousingCompatibilityStatus::RequiresData,
            $typologyResult === TypologyAdequacyResult::RequiresManualReview
                || (
                    $unit->reserved_for_special_condition !== null
                    && $specialCondition === null
                )
                || (
                    $typologyResult === TypologyAdequacyResult::Inadequate
                    && (bool) data_get($parameters, 'metadata.allow_higher_typology_exception', false)
                ) => HousingCompatibilityStatus::RequiresManualReview,
            collect($checks)->contains(fn (array $check): bool => ! $check['passed']) => HousingCompatibilityStatus::Incompatible,
            default => HousingCompatibilityStatus::Compatible,
        };

        return new HousingCompatibilityResult(
            compatible: $status->isSelectable(),
            status: $status,
            checks: $checks,
            snapshot: [
                'version' => 1,
                'application_id' => $application->id,
                'contest_id' => $application->contest_id,
                'contest_housing_unit_id' => $unit->id,
                'housing_unit_id' => $unit->housing_unit_id,
                'regulatory_snapshot_id' => $regulatorySnapshot?->id,
                'regulatory_profile_id' => $profile?->id,
                'legal_regime' => $profile?->legal_regime->value,
                'profile_version' => $profile?->version,
                'evaluated_at' => $referenceDate->toIso8601String(),
                'household_members' => $memberCount,
                'annual_income' => $annualIncome,
                'annual_income_limit' => $annualLimit,
                'annual_income_limit_evidence' => $annualLimitResult->toArray(),
                'monthly_income' => $monthlyIncome,
                'monthly_rent' => $rent,
                'maximum_effort_rate_percentage' => $maximumEffortRate,
                'calculated_effort_rate_percentage' => $effortRate,
                'typology' => $typologyCode?->label,
                'min_occupants' => $unit->min_occupants,
                'max_occupants' => $unit->max_occupants,
                'requires_accessibility' => $requiresAccessibility,
                'reserved_for_special_condition' => $unit->reserved_for_special_condition,
                'special_condition_match' => $specialCondition,
                'status' => $status->value,
                'checks' => $checks,
            ],
        );
    }

    /**
     * @param  EloquentCollection<int, HouseholdMember>|Collection<int, HouseholdMember>  $members
     */
    private function incomeIsComplete(EloquentCollection|Collection $members): bool
    {
        return $members->isNotEmpty()
            && $members->every(
                fn (HouseholdMember $member): bool => (bool) $member->has_no_income
                    || $member->incomeRecords->isNotEmpty(),
            );
    }

    private function monthlyIncome(Application $application): ?string
    {
        $household = $application->getRelationValue('household');

        if (! $household instanceof Household) {
            return null;
        }

        if (! $this->incomeIsComplete($household->members)) {
            return null;
        }

        $amounts = $household->incomeRecords
            ->map(function (IncomeRecord $record): ?string {
                $monthly = $this->decimal($record->monthly_amount);

                if ($monthly !== null) {
                    return $monthly;
                }

                $annual = $this->decimal($record->annual_amount);

                return $annual !== null
                    ? DecimalMoney::divide($annual, 12)
                    : null;
            });

        return $amounts->containsStrict(null)
            ? null
            : DecimalMoney::sum($amounts);
    }

    private function annualIncome(Application $application): ?string
    {
        $household = $application->getRelationValue('household');

        if (! $household instanceof Household) {
            return null;
        }

        if (! $this->incomeIsComplete($household->members)) {
            return null;
        }

        $amounts = $household->incomeRecords
            ->map(function (IncomeRecord $record): ?string {
                $annual = $this->decimal($record->annual_amount);

                if ($annual !== null) {
                    return $annual;
                }

                $monthly = $this->decimal($record->monthly_amount);

                return $monthly !== null
                    ? DecimalMoney::multiply($monthly, 12)
                    : null;
            });

        return $amounts->containsStrict(null)
            ? null
            : DecimalMoney::sum($amounts);
    }

    /**
     * @param  EloquentCollection<int, HouseholdMember>|Collection<int, HouseholdMember>  $members
     */
    private function adultsMeetMinimumIncome(
        EloquentCollection|Collection $members,
        ?string $minimum,
    ): bool {
        if ($minimum === null) {
            return true;
        }

        return $members
            ->filter(fn (HouseholdMember $member): bool => ($member->age() ?? 0) >= 18 && ! $member->is_dependent)
            ->every(function (HouseholdMember $member) use ($minimum): bool {
                $monthly = $member->has_no_income
                    ? '0.00'
                    : $this->memberMonthlyIncome($member);

                return $monthly !== null
                    && DecimalMoney::compare($monthly, $minimum) >= 0;
            });
    }

    private function memberMonthlyIncome(HouseholdMember $member): ?string
    {
        $amounts = $member->incomeRecords->map(
            function (IncomeRecord $record): ?string {
                $monthly = $this->decimal($record->monthly_amount);

                if ($monthly !== null) {
                    return $monthly;
                }

                $annual = $this->decimal($record->annual_amount);

                return $annual !== null
                    ? DecimalMoney::divide($annual, 12)
                    : null;
            },
        );

        return $amounts->containsStrict(null)
            ? null
            : DecimalMoney::sum($amounts);
    }

    /**
     * @return EloquentCollection<int, HouseholdMember>
     */
    private function membersFor(Application $application): EloquentCollection
    {
        $household = $application->getRelationValue('household');

        return $household instanceof Household
            ? $household->members
            : new EloquentCollection;
    }

    /**
     * @param  EloquentCollection<int, HouseholdMember>|Collection<int, HouseholdMember>  $members
     */
    private function specialConditionMatch(
        ?string $condition,
        EloquentCollection|Collection $members,
        Application $application,
    ): ?bool {
        if ($condition === null || trim($condition) === '') {
            return true;
        }

        return match (strtolower(trim($condition))) {
            'accessibility', 'reduced_mobility' => $members->contains(
                fn (HouseholdMember $member): bool => (bool) $member->has_reduced_mobility,
            ) || (bool) $application->currentHousingSituation?->has_accessibility_needs,
            'disability' => $members->contains(
                fn (HouseholdMember $member): bool => (bool) $member->is_disabled,
            ),
            'multiple_disabilities' => $members->contains(
                fn (HouseholdMember $member): bool => (bool) $member->has_multiple_disabilities,
            ),
            'pregnancy' => $members->contains(
                fn (HouseholdMember $member): bool => (bool) $member->is_pregnant,
            ),
            'elderly' => $members->contains(
                fn (HouseholdMember $member): bool => (bool) $member->is_elderly,
            ),
            'domestic_violence' => (bool) $application
                ->currentHousingSituation?->is_domestic_violence_victim,
            'homeless' => (bool) $application->currentHousingSituation?->is_homeless,
            'temporary_accommodation' => (bool) $application
                ->currentHousingSituation?->is_temporary_accommodation,
            default => null,
        };
    }

    /**
     * @return array{key: string, label: string, passed: bool, message: string}
     */
    private function check(
        string $key,
        string $label,
        bool $passed,
        string $message,
    ): array {
        return compact('key', 'label', 'passed', 'message');
    }

    private function decimal(mixed $value): ?string
    {
        return is_numeric($value)
            ? DecimalMoney::normalize((string) $value)
            : null;
    }
}
