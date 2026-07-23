<?php

namespace Tests\Feature\Security;

use App\Enums\SecurityAlertStatus;
use App\Models\MfaDevice;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\PermissionReview;
use App\Models\Role;
use App\Models\SecurityAlert;
use App\Models\SecurityAlertRule;
use App\Models\SecurityChecklist;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityMunicipalBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Municipality $municipalityA;

    private Municipality $municipalityB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipalityA = Municipality::factory()->create(['code' => 'SEC-A']);
        $this->municipalityB = Municipality::factory()->create(['code' => 'SEC-B']);
    }

    public function test_exact_permission_without_fixed_role_lists_only_own_municipality(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, ['security.view']);
        $localAlert = $this->alertFor($this->municipalityA, 'Alerta exclusivo A');
        $foreignAlert = $this->alertFor($this->municipalityB, 'Alerta exclusivo B');
        $foreignChecklist = SecurityChecklist::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.security.alerts.index'))
            ->assertOk()
            ->assertSee($localAlert->title)
            ->assertDontSee($foreignAlert->title);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.security.checklists.show', $foreignChecklist))
            ->assertForbidden();
    }

    public function test_permission_reviews_are_scoped_and_mutations_require_dedicated_permission(): void
    {
        $reader = $this->userWithPermissions($this->municipalityA, ['permission_reviews.view']);
        $localReview = PermissionReview::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'review_number' => 'PERM-MUN-A',
        ]);
        $foreignReview = PermissionReview::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'review_number' => 'PERM-MUN-B',
        ]);

        $this->actingAs($reader)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.security.permission-reviews.index'))
            ->assertOk()
            ->assertSee($localReview->review_number)
            ->assertDontSee($foreignReview->review_number)
            ->assertDontSee('Iniciar revisão');

        $this->actingAs($reader)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.security.permission-reviews.show', $foreignReview))
            ->assertForbidden();

        $this->actingAs($reader)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.security.permission-reviews.complete', $localReview), [
                'summary' => 'Tentativa sem permissão dedicada.',
            ])
            ->assertForbidden();

        $this->assertNull($localReview->refresh()->completed_at);
    }

    public function test_foreign_security_mutations_are_denied_without_side_effects(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'security.view',
            'security.update',
            'security.resolve',
            'security.approve',
        ]);
        $foreignAlert = $this->alertFor($this->municipalityB, 'Alerta estrangeiro');
        $foreignRule = $foreignAlert->rule;
        $foreignChecklist = SecurityChecklist::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.security.alerts.review', $foreignAlert))
            ->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.security.alerts.resolve', $foreignAlert), [
                'resolution_notes' => 'Tentativa sobre outro município.',
            ])
            ->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('backoffice.security.alert-rules.update', $foreignRule), [
                'code' => $foreignRule->code,
                'name' => 'Nome alterado indevidamente',
                'event_code' => $foreignRule->event_code,
                'severity' => $foreignRule->severity->value,
                'threshold' => 1,
                'window_minutes' => 15,
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.security.checklists.approve', $foreignChecklist))
            ->assertForbidden();

        $this->assertSame(SecurityAlertStatus::Open, $foreignAlert->refresh()->status);
        $this->assertNotSame('Nome alterado indevidamente', $foreignRule->refresh()->name);
        $this->assertNull($foreignChecklist->refresh()->approved_at);
    }

    public function test_auditor_is_read_only_and_candidate_has_no_backoffice_security_access(): void
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
        $alert = $this->alertFor($this->municipalityA, 'Alerta apenas leitura');

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.security.alerts.index'))
            ->assertOk()
            ->assertSee($alert->title)
            ->assertDontSee('Criar regra')
            ->assertDontSee('Resolver');

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.security.alerts.resolve', $alert), [
                'resolution_notes' => 'Auditor não pode resolver.',
            ])
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.security.alerts.index'))
            ->assertForbidden();

        $this->assertSame(SecurityAlertStatus::Open, $alert->refresh()->status);
    }

    public function test_mfa_permission_only_manages_authenticated_users_own_devices(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, ['security.manage_own_mfa']);
        $other = User::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'status' => 'active',
        ]);
        $ownDevice = MfaDevice::factory()->for($actor)->confirmed()->create();
        $foreignDevice = MfaDevice::factory()->for($other)->confirmed()->create();

        $this->actingAs($actor)
            ->get(route('backoffice.security.mfa.index'))
            ->assertOk();

        $this->actingAs($actor)
            ->post(route('backoffice.security.mfa.disable', $foreignDevice))
            ->assertForbidden();

        $this->assertNull($foreignDevice->refresh()->disabled_at);

        $this->actingAs($actor)
            ->post(route('backoffice.security.mfa.disable', $ownDevice))
            ->assertRedirect();

        $this->assertNotNull($ownDevice->refresh()->disabled_at);
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
            'name' => 'security_scope_'.$user->id,
            'label' => 'Âmbito de segurança '.$user->id,
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissions)->pluck('id')->all(),
        );
        $user->roles()->attach($role);

        return $user;
    }

    private function alertFor(Municipality $municipality, string $title): SecurityAlert
    {
        $rule = SecurityAlertRule::factory()->create([
            'municipality_id' => $municipality->id,
        ]);

        return SecurityAlert::factory()->create([
            'municipality_id' => $municipality->id,
            'security_alert_rule_id' => $rule->id,
            'title' => $title,
        ]);
    }
}
