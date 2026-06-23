<?php

namespace Tests\Feature;

use App\Enums\AllocationMethod;
use App\Enums\AllocationStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\ContractSignatureMethod;
use App\Enums\ContractSignatureRole;
use App\Enums\ContractStatus;
use App\Enums\ContractTemplateStatus;
use App\Enums\ContractValidationType;
use App\Enums\DepositStatus;
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
use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractTemplate;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HousingUnit;
use App\Models\IncomeRecord;
use App\Models\Program;
use App\Models\RentCalculation;
use App\Models\RentRuleSet;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Sprint13ContractsRentDepositTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_contract_backoffice_and_candidate_routes_are_protected_by_role_and_ownership(): void
    {
        $this->get(route('backoffice.contracts.leases.index'))
            ->assertRedirect(route('login'));

        $candidate = $this->userWithRole('candidate');
        $this->actingAs($candidate)
            ->get(route('backoffice.contracts.leases.index'))
            ->assertForbidden();

        $technician = $this->userWithRole('municipal_technician');
        $this->actingAs($technician)
            ->get(route('backoffice.contracts.leases.index'))
            ->assertOk();

        [$administrator, $allocation, $ruleSet] = $this->readyAllocationContext();
        $calculation = $this->approvedCalculation($administrator, $allocation, $ruleSet);
        $template = $this->contractTemplate($administrator, $allocation);
        $contract = $this->contractFromAllocation($administrator, $allocation, $calculation, $template);

        $otherCandidate = $this->userWithRole('candidate');
        $this->actingAs($otherCandidate)
            ->get(route('candidate.contracts.show', $contract))
            ->assertForbidden();

        $this->actingAs($contract->candidate)
            ->get(route('candidate.contracts.show', $contract))
            ->assertOk()
            ->assertSee($contract->contract_number);
    }

    public function test_backoffice_calculates_and_approves_rent_from_active_rule_set(): void
    {
        [$administrator, $allocation, $ruleSet] = $this->readyAllocationContext();

        $this->actingAs($allocation->candidate)
            ->post(route('backoffice.contracts.rent-calculations.calculate'), [
                'allocation_id' => $allocation->id,
                'rent_rule_set_id' => $ruleSet->id,
            ])
            ->assertForbidden();

        $this->actingAs($administrator)
            ->post(route('backoffice.contracts.rent-calculations.calculate'), [
                'allocation_id' => $allocation->id,
                'rent_rule_set_id' => $ruleSet->id,
                'notes' => 'Cálculo fictício de teste Sprint 13.',
            ])
            ->assertRedirect();

        $calculation = RentCalculation::query()->firstOrFail();

        $this->assertSame(RentCalculationStatus::Calculated, $calculation->status);
        $this->assertSame('300.00', $calculation->applicable_rent);
        $this->assertSame('600.00', $calculation->deposit_amount);
        $this->assertDatabaseCount('rent_calculation_details', 5);

        $this->actingAs($administrator)
            ->post(route('backoffice.contracts.rent-calculations.approve', $calculation), [
                'notes' => 'Aprovação técnica de teste.',
            ])
            ->assertRedirect();

        $this->assertSame(RentCalculationStatus::Approved, $calculation->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'contracts',
            'action' => 'rent_calculation_approve',
        ]);
    }

    public function test_backoffice_creates_contract_from_approved_calculation_and_ignores_critical_mass_assignment(): void
    {
        [$administrator, $allocation, $ruleSet] = $this->readyAllocationContext();
        $calculation = $this->approvedCalculation($administrator, $allocation, $ruleSet);
        $template = $this->contractTemplate($administrator, $allocation);

        $this->actingAs($administrator)
            ->post(route('backoffice.contracts.leases.store'), [
                'allocation_id' => $allocation->id,
                'rent_calculation_id' => $calculation->id,
                'contract_template_id' => $template->id,
                'start_date' => today()->addMonth()->format('Y-m-d'),
                'end_date' => today()->addMonths(13)->format('Y-m-d'),
                'duration_months' => 12,
                'monthly_rent' => 1,
                'deposit_amount' => 1,
                'payment_day' => 8,
                'status' => ContractStatus::Active->value,
                'contract_number' => 'FORCED-S13',
            ])
            ->assertRedirect();

        $contract = Contract::query()->processual()->with(['deposit', 'clauses', 'parties'])->firstOrFail();

        $this->assertSame(ContractStatus::Preparation, $contract->status);
        $this->assertNotSame('FORCED-S13', $contract->contract_number);
        $this->assertSame('300.00', $contract->monthly_rent);
        $this->assertSame('600.00', $contract->deposit_amount);
        $this->assertSame(2, $contract->parties()->count());
        $this->assertSame(1, $contract->clauses()->count());
        $this->assertSame(DepositStatus::Pending, $contract->deposit->status);
        $this->assertSame('600.00', $contract->deposit->amount);
        $this->assertDatabaseHas('official_notifications', [
            'user_id' => $allocation->user_id,
            'notification_type' => 'contract_preparation_started',
        ]);
    }

    public function test_contract_document_deposit_signature_validation_and_activation_flow(): void
    {
        Storage::fake('local');

        [$administrator, $allocation, $ruleSet] = $this->readyAllocationContext();
        $calculation = $this->approvedCalculation($administrator, $allocation, $ruleSet);
        $template = $this->contractTemplate($administrator, $allocation);
        $contract = $this->contractFromAllocation($administrator, $allocation, $calculation, $template);

        $this->actingAs($administrator)
            ->post(route('backoffice.contracts.leases.activate', $contract), [
                'activation_reason' => 'Tentativa prematura.',
                'confirm_activation' => '1',
            ])
            ->assertSessionHasErrors('contract');

        $this->post(route('backoffice.contracts.documents.generate', $contract))
            ->assertRedirect();

        $document = $contract->generatedDocuments()->firstOrFail();
        Storage::disk('local')->assertExists($document->storage_path);

        $this->post(route('backoffice.contracts.leases.issue', $contract), [
            'issue_notes' => 'Emissão para teste.',
        ])->assertRedirect();

        $this->post(route('backoffice.contracts.validations.store', $contract->fresh()), [
            'validation_type' => ContractValidationType::Legal->value,
            'summary' => 'Validação interna fictícia.',
        ])->assertRedirect();

        $this->post(route('backoffice.contracts.signatures.store', $contract->fresh()), [
            'signature_role' => ContractSignatureRole::Tenant->value,
            'signed_by_name' => 'Candidato de Teste',
            'signed_at' => now()->format('Y-m-d H:i:s'),
            'signature_method' => ContractSignatureMethod::Manual->value,
            'signature_reference' => 'REG-MANUAL-S13',
        ])->assertRedirect();

        $this->post(route('backoffice.contracts.deposits.paid', $contract->fresh()->deposit), [
            'paid_at' => now()->format('Y-m-d'),
            'payment_reference' => 'REF-S13',
            'receipt_reference' => 'REC-S13',
        ])->assertRedirect();

        $this->post(route('backoffice.contracts.leases.activate', $contract->fresh()), [
            'activation_reason' => 'Ativação processual de teste.',
            'confirm_activation' => '1',
        ])->assertRedirect();

        $this->assertSame(ContractStatus::Active, $contract->refresh()->status);
        $this->assertSame(DepositStatus::Paid, $contract->deposit->refresh()->status);
        $this->assertSame(HousingUnitStatus::Occupied, $allocation->housingUnit->refresh()->status);
        $this->assertSame(ContestHousingUnitStatus::Accepted, $allocation->contestHousingUnit->refresh()->status);

        $otherCandidate = $this->userWithRole('candidate');
        $this->actingAs($otherCandidate)
            ->get(route('candidate.contracts.documents.download', $document))
            ->assertForbidden();

        $this->actingAs($allocation->candidate)
            ->get(route('candidate.contracts.documents.download', $document))
            ->assertOk();
    }

    private function readyAllocationContext(): array
    {
        $administrator = $this->userWithRole('administrator');
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();
        $candidate = $this->userWithRole('candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create([
            'email' => $candidate->email,
            'nif' => 'TEST-S13-'.fake()->unique()->numerify('#####'),
        ]);
        $household = Household::factory()->candidate($registration)->create(['members_count' => 1]);
        $member = HouseholdMember::factory()->applicant()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'birth_date' => today()->subYears(34),
            'relationship' => HouseholdRelationship::Applicant->value,
        ]);

        IncomeRecord::factory()->create([
            'household_id' => $household->id,
            'household_member_id' => $member->id,
            'adhesion_registration_id' => $registration->id,
            'monthly_amount' => 1000,
            'annual_amount' => 12000,
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
            'code' => 'HU-S13-'.fake()->unique()->numerify('###'),
            'address' => 'Rua de Teste Sprint 13, 1',
            'typology' => 'T1',
            'bedrooms' => 1,
            'status' => HousingUnitStatus::Available->value,
        ]);
        $contestHousingUnit = ContestHousingUnit::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
            'status' => ContestHousingUnitStatus::Available->value,
            'typology' => 'T1',
            'bedrooms' => 1,
            'min_occupants' => 1,
            'max_occupants' => 2,
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
            'name' => 'Regra Sprint 13',
            'status' => RentRuleSetStatus::Active->value,
            'calculation_method' => RentCalculationMethod::EffortRate->value,
            'effort_rate_percentage' => 30,
            'minimum_rent' => 100,
            'maximum_rent' => 400,
            'deposit_months' => 2,
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        return [$administrator, $allocation->fresh(['candidate', 'application.household.incomeRecords', 'housingUnit', 'contestHousingUnit']), $ruleSet];
    }

    private function approvedCalculation(User $administrator, Allocation $allocation, RentRuleSet $ruleSet): RentCalculation
    {
        $this->actingAs($administrator)
            ->post(route('backoffice.contracts.rent-calculations.calculate'), [
                'allocation_id' => $allocation->id,
                'rent_rule_set_id' => $ruleSet->id,
            ])
            ->assertRedirect();

        $calculation = RentCalculation::query()->latest('id')->firstOrFail();

        $this->post(route('backoffice.contracts.rent-calculations.approve', $calculation), [
            'notes' => 'Cálculo aprovado para contrato.',
        ])->assertRedirect();

        return $calculation->refresh();
    }

    private function contractTemplate(User $administrator, Allocation $allocation): ContractTemplate
    {
        $template = ContractTemplate::factory()->create([
            'program_id' => $allocation->program_id,
            'contest_id' => $allocation->contest_id,
            'status' => ContractTemplateStatus::Active->value,
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        ContractClause::factory()->create([
            'program_id' => $allocation->program_id,
            'contest_id' => $allocation->contest_id,
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);

        return $template;
    }

    private function contractFromAllocation(User $administrator, Allocation $allocation, RentCalculation $calculation, ContractTemplate $template): Contract
    {
        $this->actingAs($administrator)
            ->post(route('backoffice.contracts.leases.store'), [
                'allocation_id' => $allocation->id,
                'rent_calculation_id' => $calculation->id,
                'contract_template_id' => $template->id,
                'start_date' => today()->addMonth()->format('Y-m-d'),
                'end_date' => today()->addMonths(13)->format('Y-m-d'),
                'duration_months' => 12,
                'monthly_rent' => 1,
                'deposit_amount' => 1,
                'payment_day' => 8,
            ])
            ->assertRedirect();

        return Contract::query()->processual()->latest('id')->firstOrFail();
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}
