<?php

namespace Tests\Feature\Security;

use App\Enums\AnonymizationStatus;
use App\Models\AnonymizationRequest;
use App\Models\ConsentPurpose;
use App\Models\DataSubjectRequest;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\RetentionPolicy;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PrivacyMunicipalBoundaryTest extends TestCase
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

    public function test_reader_only_sees_own_municipal_records_and_global_catalogue(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'privacy.view',
            'rgpd.retention.view',
            'rgpd.anonymization.view',
        ]);
        $subjectA = User::factory()->create(['municipality_id' => $this->municipalityA->id]);
        $subjectB = User::factory()->create(['municipality_id' => $this->municipalityB->id]);
        $requestA = DataSubjectRequest::factory()->for($subjectA)->create([
            'municipality_id' => $this->municipalityA->id,
            'request_number' => 'RGPD-MUNICIPIO-A',
        ]);
        $requestB = DataSubjectRequest::factory()->for($subjectB)->create([
            'municipality_id' => $this->municipalityB->id,
            'request_number' => 'RGPD-MUNICIPIO-B',
        ]);
        ConsentPurpose::factory()->create([
            'municipality_id' => null,
            'name' => 'Finalidade global',
        ]);
        ConsentPurpose::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'name' => 'Finalidade municipal A',
        ]);
        ConsentPurpose::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'name' => 'Finalidade municipal B',
        ]);
        RetentionPolicy::factory()->create([
            'municipality_id' => null,
            'name' => 'Retenção global',
        ]);
        RetentionPolicy::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'name' => 'Retenção municipal A',
        ]);
        RetentionPolicy::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'name' => 'Retenção municipal B',
        ]);
        AnonymizationRequest::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'user_id' => $subjectA->id,
            'request_number' => 'ANON-MUNICIPIO-A',
        ]);
        AnonymizationRequest::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'user_id' => $subjectB->id,
            'request_number' => 'ANON-MUNICIPIO-B',
        ]);

        $this->backofficeGet($actor, 'backoffice.security.privacy.requests.index')
            ->assertOk()
            ->assertSee('RGPD-MUNICIPIO-A')
            ->assertDontSee('RGPD-MUNICIPIO-B');

        $this->backofficeGet($actor, 'backoffice.security.privacy.purposes.index')
            ->assertOk()
            ->assertSee('Finalidade global')
            ->assertSee('Finalidade municipal A')
            ->assertDontSee('Finalidade municipal B');

        $this->backofficeGet($actor, 'backoffice.security.privacy.retention.index')
            ->assertOk()
            ->assertSee('Retenção global')
            ->assertSee('Retenção municipal A')
            ->assertDontSee('Retenção municipal B');

        $this->backofficeGet($actor, 'backoffice.security.privacy.anonymization.index')
            ->assertOk()
            ->assertSee('ANON-MUNICIPIO-A')
            ->assertDontSee('ANON-MUNICIPIO-B');

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.security.privacy.requests.show', $requestB))
            ->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.rgpd.show', $requestB))
            ->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.rgpd.show', $requestA))
            ->assertOk();
    }

    public function test_cross_municipal_mutations_are_refused_without_side_effects(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'privacy.assign',
            'privacy.update',
            'rgpd.retention.manage',
            'rgpd.anonymization.approve',
        ]);
        $subjectB = User::factory()->create(['municipality_id' => $this->municipalityB->id]);
        $requestB = DataSubjectRequest::factory()->for($subjectB)->create([
            'municipality_id' => $this->municipalityB->id,
            'assigned_to' => null,
        ]);
        $purposeB = ConsentPurpose::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'name' => 'Finalidade B intacta',
        ]);
        $policyB = RetentionPolicy::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);
        $anonymizationB = AnonymizationRequest::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'user_id' => $subjectB->id,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.security.privacy.requests.assign', $requestB), [
                'assigned_to' => $actor->id,
            ])
            ->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('backoffice.security.privacy.purposes.update', $purposeB), [
                'code' => $purposeB->code,
                'name' => 'Finalidade alterada indevidamente',
                'description' => $purposeB->description,
                'legal_basis' => $purposeB->legal_basis->value,
            ])
            ->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.security.privacy.retention.simulate', $policyB))
            ->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.security.privacy.anonymization.approve', $anonymizationB))
            ->assertForbidden();

        $this->assertNull($requestB->refresh()->assigned_to);
        $this->assertSame('Finalidade B intacta', $purposeB->refresh()->name);
        $this->assertDatabaseCount('retention_executions', 0);
        $this->assertSame(AnonymizationStatus::Draft, $anonymizationB->refresh()->status);
    }

    public function test_assignment_validation_rejects_user_from_another_municipality(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, ['privacy.assign']);
        $foreignAssignee = User::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);
        $request = DataSubjectRequest::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'assigned_to' => null,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.security.privacy.requests.assign', $request), [
                'assigned_to' => $foreignAssignee->id,
            ])
            ->assertSessionHasErrors('assigned_to');

        $this->assertNull($request->refresh()->assigned_to);
    }

    public function test_auditor_is_read_only_and_candidate_keeps_own_privacy_flow(): void
    {
        $auditor = User::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'status' => 'active',
        ]);
        $auditor->assignRole('auditor');
        $candidate = User::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'status' => 'active',
        ]);
        $candidate->assignRole('candidate');

        $this->backofficeGet($auditor, 'backoffice.security.privacy.requests.index')
            ->assertOk()
            ->assertDontSee('Registar pedido');
        $this->backofficeGet($auditor, 'backoffice.security.privacy.retention.index')
            ->assertOk()
            ->assertDontSee('Criar política');
        $this->backofficeGet($auditor, 'backoffice.security.privacy.anonymization.index')
            ->assertOk()
            ->assertDontSee('Criar pedido');

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.security.privacy.requests.store'), [
                'request_type' => 'access',
                'description' => 'Auditor não pode criar este pedido RGPD.',
            ])
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.security.privacy.requests.index'))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->post(route('candidate.privacy.requests.store'), [
                'request_type' => 'access',
                'description' => 'Pedido próprio do titular para confirmar o fluxo candidato.',
            ])
            ->assertRedirect();

        $request = DataSubjectRequest::query()
            ->where('user_id', $candidate->id)
            ->firstOrFail();

        $this->assertSame($this->municipalityA->id, $request->municipality_id);
        $this->actingAs($candidate)
            ->get(route('candidate.privacy.requests.show', $request))
            ->assertOk();
    }

    private function backofficeGet(User $actor, string $routeName): TestResponse
    {
        return $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route($routeName));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(Municipality $municipality, array $permissions): User
    {
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $role = Role::query()->create([
            'municipality_id' => $municipality->id,
            'name' => 'privacy_scope_'.$user->id,
            'label' => 'Âmbito RGPD '.$user->id,
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
        $role->permissions()->sync(
            Permission::query()
                ->whereIn('name', $permissions)
                ->pluck('id')
                ->all(),
        );
        $user->roles()->attach($role);

        return $user;
    }
}
