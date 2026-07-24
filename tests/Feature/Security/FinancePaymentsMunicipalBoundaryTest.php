<?php

namespace Tests\Feature\Security;

use App\Enums\ContractStatus;
use App\Enums\LeasePaymentStatus;
use App\Enums\PaymentImportStatus;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\HousingUnit;
use App\Models\LeasePayment;
use App\Models\Municipality;
use App\Models\PaymentImportBatch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FinancePaymentsMunicipalBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Municipality $municipalityA;

    private Municipality $municipalityB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipalityA = Municipality::factory()->create();
        $this->municipalityB = Municipality::factory()->create();
    }

    public function test_accounts_and_payments_are_scoped_to_actor_municipality(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'finance.accounts.view',
            'payments.view',
        ]);
        $localAccount = $this->accountFor($this->municipalityA, 'ACC-47F-LOCAL');
        $foreignAccount = $this->accountFor($this->municipalityB, 'ACC-47F-FOREIGN');
        $localPayment = $this->paymentFor($localAccount, 'PAY-47F-LOCAL');
        $foreignPayment = $this->paymentFor($foreignAccount, 'PAY-47F-FOREIGN');

        $this->getAs($actor, route('backoffice.finance.accounts.index'))
            ->assertOk()
            ->assertSee($localAccount->account_number)
            ->assertDontSee($foreignAccount->account_number);
        $this->getAs($actor, route('backoffice.finance.payments.index'))
            ->assertOk()
            ->assertSee($localPayment->payment_number)
            ->assertDontSee($foreignPayment->payment_number);

        $this->getAs(
            $actor,
            route('backoffice.finance.accounts.show', $foreignAccount),
        )->assertForbidden();
        $this->getAs(
            $actor,
            route('backoffice.finance.payments.show', $foreignPayment),
        )->assertForbidden();
    }

    public function test_cross_municipality_payment_mutation_is_denied_without_side_effects(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'payments.confirm',
            'payments.reverse',
        ]);
        $foreignAccount = $this->accountFor($this->municipalityB, 'ACC-47F-PROTECTED');
        $foreignPayment = $this->paymentFor(
            $foreignAccount,
            'PAY-47F-PROTECTED',
            LeasePaymentStatus::Pending,
        );

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.finance.payments.confirm', $foreignPayment))
            ->assertForbidden();
        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.finance.payments.reverse', $foreignPayment), [
                'reason' => 'Tentativa fora do âmbito municipal.',
            ])
            ->assertForbidden();

        $foreignPayment->refresh();
        $this->assertSame(LeasePaymentStatus::Pending, $foreignPayment->status);
        $this->assertNull($foreignPayment->confirmed_at);
        $this->assertNull($foreignPayment->reversed_at);
    }

    public function test_permission_and_municipal_scope_are_independent_guards(): void
    {
        $permissionWithoutScope = $this->userWithPermissions(
            null,
            ['finance.accounts.view'],
        );
        $scopeWithoutPermission = $this->userWithPermissions(
            $this->municipalityA,
            [],
        );

        $this->getAs(
            $permissionWithoutScope,
            route('backoffice.finance.accounts.index'),
        )->assertForbidden();
        $this->getAs(
            $scopeWithoutPermission,
            route('backoffice.finance.accounts.index'),
        )->assertForbidden();
    }

    public function test_candidate_auditor_inactive_account_and_inactive_role_remain_fail_closed(): void
    {
        $candidate = $this->userWithPermissions(
            $this->municipalityA,
            ['finance.accounts.view'],
            systemRole: 'candidate',
        );
        $auditor = $this->userWithPermissions(
            $this->municipalityA,
            ['finance.accounts.view', 'payments.confirm'],
            systemRole: 'auditor',
        );
        $inactiveAccount = $this->userWithPermissions(
            $this->municipalityA,
            ['finance.accounts.view'],
            status: 'inactive',
        );
        $inactiveRole = $this->userWithPermissions(
            $this->municipalityA,
            ['finance.accounts.view'],
            activeRole: false,
        );
        $account = $this->accountFor($this->municipalityA, 'ACC-47F-ROLES');
        $payment = $this->paymentFor(
            $account,
            'PAY-47F-AUDITOR',
            LeasePaymentStatus::Pending,
        );

        $this->getAs(
            $candidate,
            route('backoffice.finance.accounts.index'),
        )->assertForbidden();
        $this->getAs(
            $auditor,
            route('backoffice.finance.accounts.show', $account),
        )->assertOk();
        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.finance.payments.confirm', $payment))
            ->assertForbidden();
        $this->getAs(
            $inactiveAccount,
            route('backoffice.finance.accounts.index'),
        )->assertForbidden();
        $this->getAs(
            $inactiveRole,
            route('backoffice.finance.accounts.index'),
        )->assertForbidden();

        $this->assertSame(
            LeasePaymentStatus::Pending,
            $payment->refresh()->status,
        );
    }

    public function test_mfa_is_required_independently_from_permission(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['finance.accounts.view'],
            mfaRequired: true,
        );

        $this->actingAs($actor)
            ->get(route('backoffice.finance.accounts.index'))
            ->assertRedirect(route('backoffice.security.mfa.index'));
    }

    public function test_import_batches_use_explicit_municipality_and_null_history_fails_closed(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['payments.imports.view'],
        );
        $local = $this->importBatchFor($this->municipalityA, 'IMP-47F-LOCAL');
        $foreign = $this->importBatchFor($this->municipalityB, 'IMP-47F-FOREIGN');
        $historical = $this->importBatchFor(null, 'IMP-47F-NULL');

        $this->getAs($actor, route('backoffice.finance.imports.index'))
            ->assertOk()
            ->assertSee($local->batch_number)
            ->assertDontSee($foreign->batch_number)
            ->assertDontSee($historical->batch_number);

        $this->getAs(
            $actor,
            route('backoffice.finance.imports.show', $foreign),
        )->assertForbidden();
        $this->getAs(
            $actor,
            route('backoffice.finance.imports.show', $historical),
        )->assertForbidden();
    }

    private function accountFor(
        Municipality $municipality,
        string $number,
    ): TenantFinancialAccount {
        $tenant = User::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $citizen = Citizen::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $housingUnit = HousingUnit::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $contract = Contract::factory()->create([
            'citizen_id' => $citizen->id,
            'housing_unit_id' => $housingUnit->id,
            'user_id' => $tenant->id,
            'status' => ContractStatus::Active,
            'monthly_rent' => 300,
        ]);

        return TenantFinancialAccount::factory()->create([
            'lease_contract_id' => $contract->id,
            'user_id' => $tenant->id,
            'housing_unit_id' => $housingUnit->id,
            'account_number' => $number,
        ]);
    }

    private function paymentFor(
        TenantFinancialAccount $account,
        string $number,
        LeasePaymentStatus $status = LeasePaymentStatus::Confirmed,
    ): LeasePayment {
        return LeasePayment::factory()->create([
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $account->lease_contract_id,
            'user_id' => $account->user_id,
            'payment_number' => $number,
            'status' => $status,
            'confirmed_at' => $status === LeasePaymentStatus::Pending ? null : now(),
        ]);
    }

    private function importBatchFor(
        ?Municipality $municipality,
        string $number,
    ): PaymentImportBatch {
        $batch = new PaymentImportBatch;
        $batch->forceFill([
            'municipality_id' => $municipality?->id,
            'batch_number' => $number,
            'status' => PaymentImportStatus::Draft,
        ])->save();

        return $batch;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(
        ?Municipality $municipality,
        array $permissions,
        bool $activeRole = true,
        bool $mfaRequired = false,
        string $status = 'active',
        ?string $systemRole = null,
    ): User {
        $role = Role::query()->create([
            'municipality_id' => $municipality?->id,
            'name' => 'sprint_47f_'.str()->random(12),
            'label' => 'Teste 47F',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => $activeRole,
        ]);
        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);
        $role->permissions()->sync($permissionIds);

        $user = User::factory()->create([
            'municipality_id' => $municipality?->id,
            'status' => $status,
            'mfa_required' => $mfaRequired,
        ]);
        $user->roles()->attach($role);

        if ($systemRole !== null) {
            $user->assignRole($systemRole);
        }

        return $user;
    }

    private function getAs(User $user, string $url): TestResponse
    {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get($url);
    }
}
