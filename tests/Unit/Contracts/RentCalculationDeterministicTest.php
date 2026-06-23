<?php

namespace Tests\Unit\Contracts;

use App\Enums\AllocationMethod;
use App\Enums\AllocationStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\HouseholdRelationship;
use App\Enums\HousingUnitStatus;
use App\Enums\RentCalculationMethod;
use App\Enums\RentCalculationStatus;
use App\Enums\RentRuleSetStatus;
use App\Models\AdhesionRegistration;
use App\Models\Allocation;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HousingUnit;
use App\Models\IncomeRecord;
use App\Models\Program;
use App\Models\RentRuleSet;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\RentCalculationService;
use App\Services\Contracts\RentEffortRateService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentCalculationDeterministicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_effort_rate_returns_percentage_and_null_for_zero_income(): void
    {
        $service = app(RentEffortRateService::class);

        $this->assertSame(30.0, $service->calculate(300, 1000));
        $this->assertNull($service->calculate(300, 0));
    }

    public function test_rent_calculation_applies_effort_rate_bounds_and_deposit(): void
    {
        [$administrator, $allocation, $ruleSet] = $this->rentContext(monthlyIncome: 1000);

        $calculation = app(RentCalculationService::class)->calculate($allocation, $administrator, $ruleSet);

        $this->assertSame(RentCalculationStatus::Calculated, $calculation->status);
        $this->assertSame('350.00', $calculation->base_rent);
        $this->assertSame('350.00', $calculation->applicable_rent);
        $this->assertSame('700.00', $calculation->deposit_amount);
        $this->assertSame('35.0000', $calculation->calculated_effort_rate_percentage);
        $this->assertDatabaseCount('rent_calculation_details', 5);
    }

    public function test_zero_monthly_income_requires_manual_review(): void
    {
        [$administrator, $allocation, $ruleSet] = $this->rentContext(monthlyIncome: 0);

        $calculation = app(RentCalculationService::class)->calculate($allocation, $administrator, $ruleSet);

        $this->assertSame(RentCalculationStatus::RequiresManualReview, $calculation->status);
        $this->assertSame('100.00', $calculation->applicable_rent);
        $this->assertSame('200.00', $calculation->deposit_amount);
        $this->assertStringContainsString('Rendimento mensal inexistente', $calculation->technical_notes);
    }

    private function rentContext(float $monthlyIncome): array
    {
        $administrator = $this->userWithRole('administrator');
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();
        $candidate = $this->userWithRole('candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create([
            'email' => $candidate->email,
            'nif' => 'S19RENT'.fake()->unique()->numerify('####'),
        ]);
        $household = Household::factory()->candidate($registration)->create(['members_count' => 1]);
        $member = HouseholdMember::factory()->applicant()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'birth_date' => today()->subYears(35),
            'relationship' => HouseholdRelationship::Applicant->value,
            'has_no_income' => $monthlyIncome <= 0,
        ]);
        IncomeRecord::factory()->create([
            'household_id' => $household->id,
            'household_member_id' => $member->id,
            'adhesion_registration_id' => $registration->id,
            'monthly_amount' => $monthlyIncome,
            'annual_amount' => $monthlyIncome * 12,
        ]);
        $housing = CurrentHousingSituation::factory()->create(['adhesion_registration_id' => $registration->id]);
        $application = Application::factory()->submitted()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
            'status' => ApplicationStatus::Submitted->value,
        ]);
        $housingUnit = HousingUnit::factory()->create([
            'code' => 'S19-RENT-'.fake()->unique()->numerify('###'),
            'status' => HousingUnitStatus::Available->value,
        ]);
        $contestHousingUnit = ContestHousingUnit::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
            'status' => ContestHousingUnitStatus::Available->value,
            'monthly_rent' => 350,
        ]);
        $allocation = Allocation::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'contest_housing_unit_id' => $contestHousingUnit->id,
            'housing_unit_id' => $housingUnit->id,
            'allocation_method' => AllocationMethod::Ranking->value,
            'status' => AllocationStatus::ReadyForContract->value,
            'accepted_at' => now(),
            'ready_for_contract_at' => now(),
            'allocated_by' => $administrator->id,
        ]);
        $ruleSet = RentRuleSet::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'status' => RentRuleSetStatus::Active->value,
            'calculation_method' => RentCalculationMethod::EffortRate->value,
            'effort_rate_percentage' => 35,
            'minimum_rent' => 100,
            'maximum_rent' => 400,
            'deposit_months' => 2,
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        return [
            $administrator,
            $allocation->fresh(['application.household.incomeRecords', 'housingUnit', 'contestHousingUnit']),
            $ruleSet,
        ];
    }

    private function userWithRole(string $role): User
    {
        $this->assertTrue(Role::query()->where('name', $role)->exists());
        $user = User::factory()->create(['email' => 's19-'.$role.'-'.fake()->unique()->numberBetween(1000, 9999).'@example.test']);
        $user->assignRole($role);

        return $user;
    }
}
