<?php

namespace Tests\Feature\Security;

use App\Enums\ContractSignatureMethod;
use App\Enums\ContractSignatureRole;
use App\Enums\ContractStatus;
use App\Enums\ContractValidationStatus;
use App\Enums\ContractValidationType;
use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\HousingUnit;
use App\Models\LeaseContractSignature;
use App\Models\LeaseContractStatusHistory;
use App\Models\LeaseContractValidation;
use App\Models\Municipality;
use App\Models\User;
use App\Services\Contracts\LeaseContractSignatureService;
use App\Services\Contracts\LeaseContractStatusService;
use App\Services\Contracts\LeaseContractValidationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContractLifecycleSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private Municipality $municipality;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipality = Municipality::factory()->create();
        $this->actor = User::factory()->create([
            'municipality_id' => $this->municipality->id,
        ]);
        $this->actor->assignRole('administrator');
    }

    public function test_generic_update_cannot_force_a_lifecycle_status(): void
    {
        $contract = $this->contract(ContractStatus::Preparation);

        $this->actingAs($this->actor)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('contracts.update', $contract), [
                'citizen_id' => $contract->citizen_id,
                'housing_unit_id' => $contract->housing_unit_id,
                'start_date' => now()->toDateString(),
                'monthly_rent' => 325,
                'status' => ContractStatus::Active->value,
            ])
            ->assertRedirect(route('contracts.index'))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame(ContractStatus::Preparation, $contract->status);
        $this->assertSame('325.00', $contract->monthly_rent);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'contracts',
            'action' => 'contract_update',
            'auditable_id' => $contract->id,
        ]);
    }

    public function test_status_transitions_are_stateful_audited_and_idempotent(): void
    {
        $service = app(LeaseContractStatusService::class);
        $contract = $this->contract(ContractStatus::Preparation);

        $issued = $service->transition(
            $contract,
            ContractStatus::Issued,
            $this->actor,
            'Emissão validada em teste.',
        );
        $historyCount = LeaseContractStatusHistory::query()->count();
        $auditCount = AuditLog::query()
            ->where('action', 'lease_contract_status_issued')
            ->count();

        $again = $service->transition(
            $issued,
            ContractStatus::Issued,
            $this->actor,
            'Repetição idempotente.',
        );

        $this->assertSame(ContractStatus::Issued, $again->status);
        $this->assertSame($historyCount, LeaseContractStatusHistory::query()->count());
        $this->assertSame(
            $auditCount,
            AuditLog::query()
                ->where('action', 'lease_contract_status_issued')
                ->count(),
        );
    }

    public function test_invalid_transition_is_rejected_without_side_effects(): void
    {
        $contract = $this->contract(ContractStatus::Preparation);
        $service = app(LeaseContractStatusService::class);

        try {
            $service->transition(
                $contract,
                ContractStatus::Active,
                $this->actor,
                'Transição inválida.',
            );
            $this->fail('A transição inválida deveria ter sido recusada.');
        } catch (ValidationException) {
            $this->assertSame(
                ContractStatus::Preparation,
                $contract->refresh()->status,
            );
            $this->assertSame(0, $contract->statusHistories()->count());
        }
    }

    public function test_signature_registration_is_idempotent_and_moves_issued_contract_to_signed(): void
    {
        $contract = $this->contract(ContractStatus::Issued);
        $data = [
            'signature_role' => ContractSignatureRole::Tenant->value,
            'signed_by_name' => 'Pessoa signatária de teste',
            'signed_at' => now()->startOfSecond()->toDateTimeString(),
            'signature_method' => ContractSignatureMethod::Manual->value,
            'signature_reference' => 'SIG-47E-IDEMPOTENT',
        ];
        $service = app(LeaseContractSignatureService::class);

        $first = $service->store($contract, $this->actor, $data);
        $second = $service->store($contract->refresh(), $this->actor, $data);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, LeaseContractSignature::query()->count());
        $this->assertSame(ContractStatus::Signed, $contract->refresh()->status);
        $this->assertSame(
            1,
            AuditLog::query()
                ->where('action', 'lease_contract_signature_store')
                ->count(),
        );
    }

    public function test_validation_approval_and_rejection_are_idempotent_and_mutually_exclusive(): void
    {
        $contract = $this->contract(ContractStatus::Issued);
        $service = app(LeaseContractValidationService::class);
        $data = [
            'validation_type' => ContractValidationType::Legal->value,
            'summary' => 'Validação jurídica de teste.',
        ];

        $first = $service->approve($contract, $this->actor, $data);
        $second = $service->approve($contract, $this->actor, $data);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, LeaseContractValidation::query()->count());
        $this->assertSame(ContractValidationStatus::Approved, $second->status);

        try {
            $service->reject(
                $second,
                $this->actor,
                'Tentativa de rejeitar validação já aprovada.',
            );
            $this->fail('A validação aprovada não pode ser rejeitada.');
        } catch (ValidationException) {
            $this->assertSame(
                ContractValidationStatus::Approved,
                $second->refresh()->status,
            );
            $this->assertSame(
                0,
                AuditLog::query()
                    ->where('action', 'lease_contract_validation_reject')
                    ->count(),
            );
        }
    }

    public function test_read_only_auditor_cannot_execute_lifecycle_mutation(): void
    {
        $auditor = User::factory()->create([
            'municipality_id' => $this->municipality->id,
        ]);
        $auditor->assignRole('auditor');
        $contract = $this->contract(ContractStatus::Active);

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.contracts.leases.suspend', $contract), [
                'reason' => 'O auditor não pode alterar o contrato.',
            ])
            ->assertForbidden();

        $this->assertSame(ContractStatus::Active, $contract->refresh()->status);
    }

    private function contract(ContractStatus $status): Contract
    {
        $citizen = Citizen::factory()->create([
            'municipality_id' => $this->municipality->id,
        ]);
        $housingUnit = HousingUnit::factory()->create([
            'municipality_id' => $this->municipality->id,
        ]);

        return Contract::factory()->create([
            'citizen_id' => $citizen->id,
            'housing_unit_id' => $housingUnit->id,
            'status' => $status,
            'monthly_rent' => 300,
        ]);
    }
}
