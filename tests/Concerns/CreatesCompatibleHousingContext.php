<?php

namespace Tests\Concerns;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\ApplicationStatus;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\HousingPublicStatus;
use App\Enums\HousingUnitStatus;
use App\Enums\PublicVisibilityStatus;
use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\RegulatoryContext;
use App\Models\AdhesionRegistration;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\AllocationRuleSet;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HousingUnit;
use App\Models\IncomeRecord;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\RegulatorySnapshot;
use App\Models\TypologyAdequacyRule;
use App\Models\User;

trait CreatesCompatibleHousingContext
{
    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    protected function compatibleHousingContext(
        int $memberCount = 1,
        ?string $ruleTypology = 'T2',
        ?int $minimumBedrooms = 1,
        ?int $maximumBedrooms = 2,
        AffordableRentLegalRegime $regime = AffordableRentLegalRegime::PaaLegacy2019,
        RegulatoryConfigurationStatus $configurationStatus = RegulatoryConfigurationStatus::Complete,
        array $parameters = [],
    ): array {
        $municipality = Municipality::factory()->create();
        $program = Program::factory()->published()->create([
            'municipality_id' => $municipality->id,
        ]);
        $contest = Contest::factory()->for($program)->open()->create();
        $profile = AffordableRentRegulatoryProfile::factory()->create([
            'municipality_id' => $municipality->id,
            'legal_regime' => $regime,
            'configuration_status' => $configurationStatus,
            'effective_from' => $regime === AffordableRentLegalRegime::Rsaa2026
                ? '2026-09-01'
                : '2019-07-01',
            'effective_until' => $regime === AffordableRentLegalRegime::Rsaa2026
                ? null
                : '2026-08-31',
            'rent_limits_configured' => $configurationStatus === RegulatoryConfigurationStatus::Complete,
        ]);
        $ruleSet = AllocationRuleSet::factory()->create([
            'regulatory_profile_id' => $profile->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'allow_preferences' => true,
            'minimum_preferences' => 1,
            'maximum_preferences' => 3,
            'preferences_required_before_submission' => true,
            'allow_unselected_unit_fallback' => false,
            'preference_selection_starts_at' => now()->subDay(),
            'preference_selection_ends_at' => now()->addMonth(),
        ]);
        $regulatoryParameters = array_replace([
            'maximum_effort_rate_percentage' => '35.00',
            'minimum_adult_monthly_income' => null,
            'annual_income_base_limit' => '38632.00',
            'second_person_increment' => '10000.00',
            'additional_person_increment' => '5000.00',
            'tax_year' => 2026,
            'sixth_irs_bracket_upper_limit' => '999999.00',
            'irs_source_reference' => 'TESTE-SEM-VALOR-JURIDICO',
            'irs_source_version' => 'test-fixture-2026',
            'irs_effective_from' => '2026-01-01',
            'irs_effective_until' => '2026-12-31',
            'metadata' => [
                'test_data' => true,
                'demo' => true,
                'demo_only' => true,
            ],
        ], $parameters);
        $snapshot = RegulatorySnapshot::factory()->create([
            'municipality_id' => $municipality->id,
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => $regime,
            'context' => RegulatoryContext::ContestPublication,
            'source_type' => $contest->getMorphClass(),
            'source_id' => $contest->id,
            'profile_code' => $profile->code,
            'profile_version' => $profile->version,
            'rule_sets' => [
                'allocation_rule_set_id' => $ruleSet->id,
            ],
            'parameters' => $regulatoryParameters,
        ]);

        $program->forceFill([
            'regulatory_profile_id' => $profile->id,
            'regulatory_snapshot_id' => $snapshot->id,
            'legal_regime' => $regime,
        ])->save();
        $contest->forceFill([
            'regulatory_profile_id' => $profile->id,
            'regulatory_snapshot_id' => $snapshot->id,
            'legal_regime' => $regime,
        ])->save();

        TypologyAdequacyRule::factory()->create([
            'regulatory_profile_id' => $profile->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'typology' => $ruleTypology,
            'min_household_members' => $memberCount,
            'max_household_members' => $memberCount,
            'min_bedrooms' => $minimumBedrooms,
            'max_bedrooms' => $maximumBedrooms,
        ]);

        $context = [
            'municipality' => $municipality,
            'program' => $program->fresh(),
            'contest' => $contest->fresh(),
            'profile' => $profile,
            'rule_set' => $ruleSet,
            'snapshot' => $snapshot,
        ];

        return array_merge(
            $context,
            $this->candidateApplicationForHousingContext(
                $context,
                $memberCount,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function candidateApplicationForHousingContext(
        array $context,
        int $memberCount = 1,
        string $monthlyIncome = '2000.00',
        string $annualIncome = '24000.00',
    ): array {
        /** @var Municipality $municipality */
        $municipality = $context['municipality'];
        /** @var Program $program */
        $program = $context['program'];
        /** @var Contest $contest */
        $contest = $context['contest'];
        /** @var AffordableRentRegulatoryProfile $profile */
        $profile = $context['profile'];
        /** @var RegulatorySnapshot $snapshot */
        $snapshot = $context['snapshot'];

        $candidate = User::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $candidate->assignRole('candidate');
        $registration = AdhesionRegistration::factory()
            ->registered()
            ->for($candidate)
            ->create([
                'nif' => 'TEST-50E-'.fake()->unique()->numerify('######'),
            ]);
        $household = Household::factory()->candidate($registration)->create([
            'municipality_id' => $municipality->id,
            'members_count' => $memberCount,
        ]);
        $applicant = HouseholdMember::factory()->applicant()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'birth_date' => today()->subYears(35),
            'is_dependent' => false,
        ]);
        $income = IncomeRecord::factory()->create([
            'household_member_id' => $applicant->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'monthly_amount' => $monthlyIncome,
            'annual_amount' => $annualIncome,
        ]);

        for ($index = 1; $index < $memberCount; $index++) {
            HouseholdMember::factory()->withoutIncome()->create([
                'household_id' => $household->id,
                'adhesion_registration_id' => $registration->id,
                'birth_date' => today()->subYears(8 + $index),
                'is_dependent' => true,
            ]);
        }

        $housingSituation = CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
        ]);
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housingSituation->id,
            'regulatory_snapshot_id' => $snapshot->id,
            'legal_regime' => $profile->legal_regime,
            'status' => ApplicationStatus::Draft,
        ]);

        return [
            'candidate' => $candidate,
            'registration' => $registration,
            'household' => $household,
            'applicant' => $applicant,
            'income' => $income,
            'housing_situation' => $housingSituation,
            'application' => $application,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function compatibleContestHousingUnit(
        array $context,
        string $typology = 'T2',
        int $bedrooms = 2,
        int $minimumOccupants = 1,
        int $maximumOccupants = 4,
        string $monthlyRent = '300.00',
        ?Municipality $municipality = null,
        ?Contest $contest = null,
        ContestHousingUnitStatus $status = ContestHousingUnitStatus::Available,
    ): ContestHousingUnit {
        /** @var Municipality $contextMunicipality */
        $contextMunicipality = $context['municipality'];
        /** @var Program $program */
        $program = $context['program'];
        /** @var Contest $contextContest */
        $contextContest = $context['contest'];
        $municipality ??= $contextMunicipality;
        $contest ??= $contextContest;

        $housingUnit = HousingUnit::factory()->publiclyVisible()->create([
            'municipality_id' => $municipality->id,
            'typology' => $typology,
            'bedrooms' => $bedrooms,
            'monthly_rent' => $monthlyRent,
            'status' => HousingUnitStatus::Available,
            'public_status' => HousingPublicStatus::Available,
            'public_visibility_status' => PublicVisibilityStatus::Published,
            'is_public' => true,
            'published_at' => now()->subHour(),
            'unpublished_at' => null,
        ]);

        return ContestHousingUnit::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
            'status' => $status,
            'typology' => $typology,
            'bedrooms' => $bedrooms,
            'min_occupants' => $minimumOccupants,
            'max_occupants' => $maximumOccupants,
            'monthly_rent' => $monthlyRent,
        ]);
    }
}
