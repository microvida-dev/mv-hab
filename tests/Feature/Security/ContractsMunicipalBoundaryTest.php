<?php

namespace Tests\Feature\Security;

use App\Enums\ChargeType;
use App\Enums\ContractStatus;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\ContractClause;
use App\Models\ContractTemplate;
use App\Models\HousingUnit;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\TenantChargeRun;
use App\Models\TenantCommunication;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ContractsMunicipalBoundaryTest extends TestCase
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

    public function test_contract_indexes_and_details_are_scoped_to_actor_municipality(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['contracts.view'],
        );
        $local = $this->contractFor($this->municipalityA, 'CTR-47E-LOCAL');
        $foreign = $this->contractFor($this->municipalityB, 'CTR-47E-FOREIGN');

        $this->getAs($actor, route('contracts.index'))
            ->assertOk()
            ->assertSee($local->citizen->name)
            ->assertDontSee($foreign->citizen->name);

        $this->getAs($actor, route('contracts.show', $local))->assertOk();
        $this->getAs($actor, route('contracts.show', $foreign))->assertForbidden();
        $this->getAs(
            $actor,
            route('backoffice.cases.contracts.show', $foreign),
        )->assertForbidden();
    }

    public function test_templates_and_clauses_are_filtered_by_program_municipality(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['contracts.view'],
        );
        $localProgram = Program::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);
        $foreignProgram = Program::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);
        $localTemplate = ContractTemplate::factory()->create([
            'program_id' => $localProgram->id,
            'name' => 'Minuta municipal local 47E',
        ]);
        $foreignTemplate = ContractTemplate::factory()->create([
            'program_id' => $foreignProgram->id,
            'name' => 'Minuta municipal externa 47E',
        ]);
        $localClause = ContractClause::factory()->create([
            'program_id' => $localProgram->id,
            'title' => 'Cláusula municipal local 47E',
        ]);
        $foreignClause = ContractClause::factory()->create([
            'program_id' => $foreignProgram->id,
            'title' => 'Cláusula municipal externa 47E',
        ]);

        $this->getAs($actor, route('backoffice.contracts.templates.index'))
            ->assertOk()
            ->assertSee($localTemplate->name)
            ->assertDontSee($foreignTemplate->name);
        $this->getAs($actor, route('backoffice.contracts.clauses.index'))
            ->assertOk()
            ->assertSee($localClause->title)
            ->assertDontSee($foreignClause->title);

        $this->getAs(
            $actor,
            route('backoffice.contracts.templates.show', $foreignTemplate),
        )->assertForbidden();
        $this->getAs(
            $actor,
            route('backoffice.contracts.clauses.show', $foreignClause),
        )->assertForbidden();
    }

    public function test_cross_municipality_mutations_are_denied_without_side_effects(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'contracts.update',
            'contracts.delete',
            'contracts.suspend',
        ]);
        $foreign = $this->contractFor(
            $this->municipalityB,
            'CTR-47E-PROTECTED',
            ContractStatus::Preparation,
        );
        $originalRent = $foreign->monthly_rent;

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('contracts.update', $foreign), [
                'citizen_id' => $foreign->citizen_id,
                'housing_unit_id' => $foreign->housing_unit_id,
                'start_date' => now()->toDateString(),
                'monthly_rent' => 999,
            ])
            ->assertForbidden();
        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('contracts.destroy', $foreign))
            ->assertForbidden();
        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.contracts.leases.suspend', $foreign), [
                'reason' => 'Tentativa fora do âmbito municipal.',
            ])
            ->assertForbidden();

        $foreign->refresh();
        $this->assertSame(ContractStatus::Preparation, $foreign->status);
        $this->assertSame($originalRent, $foreign->monthly_rent);
        $this->assertNull($foreign->deleted_at);
    }

    public function test_permission_and_municipal_scope_are_independent_guards(): void
    {
        $permissionWithoutScope = $this->userWithPermissions(
            null,
            ['contracts.view'],
        );
        $scopeWithoutPermission = $this->userWithPermissions(
            $this->municipalityA,
            [],
        );

        $this->getAs($permissionWithoutScope, route('contracts.index'))
            ->assertForbidden();
        $this->getAs($scopeWithoutPermission, route('contracts.index'))
            ->assertForbidden();
    }

    public function test_candidate_auditor_inactive_account_and_inactive_role_remain_fail_closed(): void
    {
        $candidate = $this->userWithPermissions(
            $this->municipalityA,
            ['contracts.view'],
            systemRole: 'candidate',
        );
        $auditor = $this->userWithPermissions(
            $this->municipalityA,
            ['contracts.view', 'contracts.delete'],
            systemRole: 'auditor',
        );
        $inactiveAccount = $this->userWithPermissions(
            $this->municipalityA,
            ['contracts.view'],
            status: 'inactive',
        );
        $inactiveRole = $this->userWithPermissions(
            $this->municipalityA,
            ['contracts.view'],
            activeRole: false,
        );
        $contract = $this->contractFor($this->municipalityA, 'CTR-47E-ROLES');

        $this->getAs($candidate, route('contracts.index'))->assertForbidden();
        $this->getAs($auditor, route('contracts.show', $contract))->assertOk();
        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('contracts.destroy', $contract))
            ->assertForbidden();
        $this->getAs($inactiveAccount, route('contracts.index'))->assertForbidden();
        $this->getAs($inactiveRole, route('contracts.index'))->assertForbidden();
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
    }

    public function test_mfa_is_required_independently_from_permission(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['contracts.view'],
            mfaRequired: true,
        );

        $this->actingAs($actor)
            ->get(route('contracts.index'))
            ->assertRedirect(route('backoffice.security.mfa.index'));
    }

    public function test_charge_run_only_aggregates_contracts_from_actor_municipality(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['contracts.charge_runs.run'],
        );
        $this->contractFor(
            $this->municipalityA,
            'CTR-47E-CHARGE-LOCAL',
            ContractStatus::Active,
            withTenant: true,
        );
        $foreign = $this->contractFor(
            $this->municipalityB,
            'CTR-47E-CHARGE-FOREIGN',
            ContractStatus::Active,
            withTenant: true,
        );

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.tenant-operations.charge-runs.store'), [
                'period_year' => now()->addMonth()->year,
                'period_month' => now()->addMonth()->month,
                'charge_type' => ChargeType::Rent->value,
            ])
            ->assertRedirect();

        $run = TenantChargeRun::query()->firstOrFail();
        $this->assertSame(1, $run->items()->count());
        $this->assertFalse(
            $run->items()->where('lease_contract_id', $foreign->id)->exists(),
        );
    }

    public function test_foreign_tenant_communication_cannot_be_read_or_changed(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'contracts.communications.view',
            'contracts.communications.message',
        ]);
        $foreignContract = $this->contractFor(
            $this->municipalityB,
            'CTR-47E-COMM-FOREIGN',
            ContractStatus::Active,
            withTenant: true,
        );
        $communication = TenantCommunication::factory()->create([
            'user_id' => $foreignContract->user_id,
            'lease_contract_id' => $foreignContract->id,
        ]);

        $this->getAs(
            $actor,
            route(
                'backoffice.tenant-operations.communications.show',
                $communication,
            ),
        )->assertForbidden();
        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route(
                    'backoffice.tenant-operations.communications.messages.store',
                    $communication,
                ),
                ['body' => 'Tentativa de mensagem fora do município.'],
            )
            ->assertForbidden();

        $this->assertSame(0, $communication->messages()->count());
    }

    private function contractFor(
        Municipality $municipality,
        string $number,
        ContractStatus $status = ContractStatus::Preparation,
        bool $withTenant = false,
    ): Contract {
        $citizen = Citizen::factory()->create([
            'municipality_id' => $municipality->id,
            'name' => 'Munícipe '.$number,
        ]);
        $housingUnit = HousingUnit::factory()->create([
            'municipality_id' => $municipality->id,
            'code' => 'HU-'.$number,
        ]);
        $tenant = $withTenant
            ? User::factory()->create(['municipality_id' => $municipality->id])
            : null;
        $contract = Contract::factory()->create([
            'citizen_id' => $citizen->id,
            'housing_unit_id' => $housingUnit->id,
            'user_id' => $tenant?->id,
            'status' => $status,
            'monthly_rent' => 300,
        ]);
        $contract->forceFill(['contract_number' => $number])->save();

        return $contract->refresh()->load('citizen');
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
            'name' => 'sprint_47e_'.str()->random(12),
            'label' => 'Teste 47E',
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
