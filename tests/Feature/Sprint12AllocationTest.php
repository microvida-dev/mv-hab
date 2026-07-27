<?php

namespace Tests\Feature;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\AllocationMethod;
use App\Enums\AllocationOfferStatus;
use App\Enums\AllocationStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\DefinitiveListStatus;
use App\Enums\HouseholdRelationship;
use App\Enums\ListEntryStatus;
use App\Enums\ListEntryType;
use App\Enums\RegulatoryContext;
use App\Models\AdhesionRegistration;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Allocation;
use App\Models\AllocationOffer;
use App\Models\AllocationRuleSet;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\CurrentHousingSituation;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HousingUnit;
use App\Models\IncomeRecord;
use App\Models\LotteryRun;
use App\Models\Program;
use App\Models\ProvisionalList;
use App\Models\RegulatorySnapshot;
use App\Models\TypologyAdequacyRule;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint12AllocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_backoffice_allocation_routes_are_protected_by_role_and_permission(): void
    {
        $this->get(route('backoffice.allocation.runs.index'))
            ->assertRedirect(route('login'));

        $candidate = $this->userWithRole('candidate');
        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.runs.index'))
            ->assertForbidden();

        $technician = $this->userWithRole('municipal_technician');
        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.runs.index'))
            ->assertOk();
    }

    public function test_backoffice_can_attach_housing_unit_to_contest_and_block_duplicate_active_assignment(): void
    {
        $administrator = $this->userWithRole('administrator');
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();
        $housingUnit = HousingUnit::factory()->create(['typology' => 'T2', 'bedrooms' => 2]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.allocation.contest-housing-units.store'), [
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'housing_unit_id' => $housingUnit->id,
                'typology' => 'T2',
                'bedrooms' => 2,
                'min_occupants' => 1,
                'max_occupants' => 4,
                'accessible' => '0',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('contest_housing_units', [
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
            'status' => ContestHousingUnitStatus::Available->value,
        ]);

        $otherContest = Contest::factory()->for($program)->open()->create();
        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.allocation.contest-housing-units.store'), [
                'program_id' => $program->id,
                'contest_id' => $otherContest->id,
                'housing_unit_id' => $housingUnit->id,
                'typology' => 'T2',
                'bedrooms' => 2,
                'min_occupants' => 1,
                'max_occupants' => 4,
                'accessible' => '0',
            ])
            ->assertSessionHasErrors('housing_unit_id');
    }

    public function test_candidate_can_store_own_housing_preferences_and_cannot_edit_another_candidate_application(): void
    {
        [$administrator, $program, $contest, $list, $applications, $units] = $this->allocationContext(
            candidateCount: 1,
            unitCount: 2,
            editableApplications: true,
        );
        $candidate = $applications[0]->user;

        $this->actingAs($candidate)
            ->patch(route('candidate.housing-preferences.update', $applications[0]), [
                'preferences' => [
                    ['contest_housing_unit_id' => $units[0]->id, 'preference_order' => 1],
                    ['contest_housing_unit_id' => $units[1]->id, 'preference_order' => 2],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('housing_preferences', [
            'application_id' => $applications[0]->id,
            'contest_housing_unit_id' => $units[0]->id,
            'preference_order' => 1,
        ]);

        $otherCandidate = $this->userWithRole('candidate');
        $this->actingAs($otherCandidate)
            ->patch(route('candidate.housing-preferences.update', $applications[0]), [
                'preferences' => [
                    ['contest_housing_unit_id' => $units[0]->id, 'preference_order' => 1],
                ],
            ])
            ->assertForbidden();

        $this->assertSame(1, $list->entries()->eligibleForAllocation()->count());
        $this->assertTrue($administrator->hasPermissionTo('allocations', 'create'));
        $this->assertSame($program->id, $contest->program_id);
    }

    public function test_allocation_run_by_ranking_creates_offer_reserve_list_report_and_internal_notification(): void
    {
        [$administrator, , , $list] = $this->allocationContext(candidateCount: 2, unitCount: 1);
        $ruleSet = AllocationRuleSet::query()->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.allocation.runs.store'), [
                'definitive_list_id' => $list->id,
                'allocation_rule_set_id' => $ruleSet->id,
                'allocation_method' => AllocationMethod::Ranking->value,
                'notes' => 'Execução fictícia de teste.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $allocation = Allocation::query()->firstOrFail();
        $offer = AllocationOffer::query()->firstOrFail();

        $this->assertSame(AllocationStatus::Offered, $allocation->status);
        $this->assertSame(AllocationOfferStatus::PendingResponse, $offer->status);
        $this->assertDatabaseCount('reserve_list_entries', 1);
        $this->assertDatabaseCount('allocation_reports', 1);
        $this->assertDatabaseHas('official_notifications', [
            'user_id' => $allocation->user_id,
            'notification_type' => 'allocation_offer_issued',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'allocations',
            'action' => 'allocation_run_execute',
        ]);
    }

    public function test_candidate_accepts_own_offer_and_other_candidate_cannot_access_it(): void
    {
        [$administrator, , , $list] = $this->allocationContext(candidateCount: 2, unitCount: 1);
        $ruleSet = AllocationRuleSet::query()->firstOrFail();
        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.allocation.runs.store'), [
                'definitive_list_id' => $list->id,
                'allocation_rule_set_id' => $ruleSet->id,
                'allocation_method' => AllocationMethod::Ranking->value,
            ])
            ->assertSessionHasNoErrors();

        $offer = AllocationOffer::query()->with('allocation.application')->firstOrFail();
        $candidate = $offer->candidate;

        $otherCandidate = $this->userWithRole('candidate');
        $this->actingAs($otherCandidate)
            ->get(route('candidate.allocation-offers.show', $offer))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->post(route('candidate.allocation-offers.accept', $offer), [
                'confirm_acceptance' => '1',
                'candidate_response' => 'Aceitação fictícia para teste.',
            ])
            ->assertRedirect();

        $allocation = $offer->allocation->fresh();
        $this->assertSame(AllocationStatus::ReadyForContract, $allocation->status);
        $this->assertNotNull($allocation->ready_for_contract_at);
        $this->assertTrue(Application::query()->readyForContract()->whereKey($offer->application_id)->exists());
        $this->assertDatabaseHas('official_notifications', [
            'user_id' => $candidate->id,
            'notification_type' => 'allocation_ready_for_contract',
        ]);
    }

    public function test_lottery_allocation_generates_audit_payload_and_auditor_can_view_it(): void
    {
        [$administrator, , , $list] = $this->allocationContext(candidateCount: 2, unitCount: 1, method: AllocationMethod::Lottery);
        $ruleSet = AllocationRuleSet::query()->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.allocation.runs.store'), [
                'definitive_list_id' => $list->id,
                'allocation_rule_set_id' => $ruleSet->id,
                'allocation_method' => AllocationMethod::Lottery->value,
                'seed' => 'SPRINT-12-SEED',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $lottery = LotteryRun::query()->firstOrFail();
        $this->assertNotNull($lottery->audit_hash);
        $this->assertDatabaseCount('lottery_participants', 2);
        $this->assertDatabaseCount('lottery_draw_results', 2);

        $auditor = $this->userWithRole('auditor');
        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.lotteries.audit', $lottery))
            ->assertOk()
            ->assertSee($lottery->audit_hash);
    }

    private function allocationContext(
        int $candidateCount,
        int $unitCount,
        AllocationMethod $method = AllocationMethod::Ranking,
        bool $editableApplications = false,
    ): array {
        $administrator = $this->userWithRole('administrator');
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();
        $municipality = $program->municipality()->firstOrFail();
        $profile = AffordableRentRegulatoryProfile::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $administrator->forceFill([
            'municipality_id' => $municipality->id,
        ])->save();
        $provisionalList = ProvisionalList::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'generated_by' => $administrator->id,
        ]);
        $list = DefinitiveList::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'provisional_list_id' => $provisionalList->id,
            'status' => DefinitiveListStatus::Locked->value,
            'generated_by' => $administrator->id,
            'approved_by' => $administrator->id,
            'published_by' => $administrator->id,
            'approved_at' => now(),
            'published_at' => now(),
        ]);

        $ruleSet = AllocationRuleSet::factory()->create([
            'regulatory_profile_id' => $profile->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'allocation_method' => $method->value,
            'requires_acceptance' => true,
            'auto_call_next_on_refusal' => true,
            'auto_call_next_on_expiry' => true,
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        TypologyAdequacyRule::factory()->create([
            'regulatory_profile_id' => $profile->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'typology' => 'T2',
            'min_household_members' => 1,
            'max_household_members' => 4,
            'min_bedrooms' => 1,
            'max_bedrooms' => 3,
        ]);
        $snapshot = RegulatorySnapshot::factory()->create([
            'municipality_id' => $municipality->id,
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
            'context' => RegulatoryContext::ContestPublication,
            'source_type' => $contest->getMorphClass(),
            'source_id' => $contest->id,
            'rule_sets' => [
                'allocation_rule_set_id' => $ruleSet->id,
            ],
            'parameters' => [
                'maximum_effort_rate_percentage' => '35.00',
                'minimum_adult_monthly_income' => null,
                'annual_income_base_limit' => '38632.00',
                'second_person_increment' => '10000.00',
                'additional_person_increment' => '5000.00',
            ],
        ]);
        $program->forceFill([
            'regulatory_profile_id' => $profile->id,
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
        ])->save();
        $contest->forceFill([
            'regulatory_profile_id' => $profile->id,
            'regulatory_snapshot_id' => $snapshot->id,
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
        ])->save();

        $units = collect();
        for ($index = 0; $index < $unitCount; $index++) {
            $housingUnit = HousingUnit::factory()->publiclyVisible()->create([
                'municipality_id' => $municipality->id,
                'code' => 'HU-S12-'.($index + 1),
                'typology' => 'T2',
                'bedrooms' => 2,
                'status' => 'available',
                'monthly_rent' => '200.00',
            ]);
            $units->push(ContestHousingUnit::factory()->create([
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'housing_unit_id' => $housingUnit->id,
                'status' => ContestHousingUnitStatus::Available->value,
                'typology' => 'T2',
                'bedrooms' => 2,
                'min_occupants' => 1,
                'max_occupants' => 4,
                'monthly_rent' => '200.00',
            ]));
        }

        $applications = collect();
        for ($rank = 1; $rank <= $candidateCount; $rank++) {
            $candidate = $this->userWithRole('candidate');
            $candidate->forceFill([
                'municipality_id' => $municipality->id,
            ])->save();
            $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create([
                'nif' => 'TEST-S12-'.fake()->unique()->numerify('#####'),
            ]);
            $household = Household::factory()->candidate($registration)->create(['members_count' => 1]);
            $member = HouseholdMember::factory()->applicant()->create([
                'household_id' => $household->id,
                'adhesion_registration_id' => $registration->id,
                'birth_date' => today()->subYears(30),
                'relationship' => HouseholdRelationship::Applicant->value,
            ]);
            IncomeRecord::factory()->create([
                'household_member_id' => $member->id,
                'household_id' => $household->id,
                'adhesion_registration_id' => $registration->id,
                'monthly_amount' => '1000.00',
                'annual_amount' => '12000.00',
            ]);
            $housing = CurrentHousingSituation::factory()->create(['adhesion_registration_id' => $registration->id]);
            $applicationFactory = Application::factory();

            if (! $editableApplications) {
                $applicationFactory = $applicationFactory->submitted();
            }

            $application = $applicationFactory->create([
                'user_id' => $candidate->id,
                'adhesion_registration_id' => $registration->id,
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'household_id' => $household->id,
                'current_housing_situation_id' => $housing->id,
                'status' => $editableApplications
                    ? ApplicationStatus::Draft->value
                    : ApplicationStatus::Submitted->value,
            ]);
            DefinitiveListEntry::factory()->create([
                'definitive_list_id' => $list->id,
                'application_id' => $application->id,
                'user_id' => $candidate->id,
                'entry_type' => ListEntryType::Ranked->value,
                'status' => ListEntryStatus::Ranked->value,
                'rank_position' => $rank,
                'total_score' => 100 - $rank,
                'public_identifier' => 'PUB-S12-'.$rank,
            ]);
            $applications->push($application->fresh(['user']));
        }

        return [$administrator, $program, $contest, $list->fresh('entries'), $applications, $units];
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}
