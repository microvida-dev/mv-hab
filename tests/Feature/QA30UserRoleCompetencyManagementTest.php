<?php

namespace Tests\Feature;

use App\Models\AccessChangeEvent;
use App\Models\MunicipalTeam;
use App\Models\Role;
use App\Models\User;
use App\Services\Security\MfaEnforcementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QA30UserRoleCompetencyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_qa30_new_institutional_profiles_are_seeded_and_bounded(): void
    {
        $expectedRoles = ['legal_manager', 'housing_manager', 'inspection_manager', 'support_agent'];

        foreach ($expectedRoles as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role, 'is_system' => true]);
        }

        $legal = $this->userWithRole('legal_manager');
        $housing = $this->userWithRole('housing_manager');
        $inspection = $this->userWithRole('inspection_manager');
        $support = $this->userWithRole('support_agent');
        $mfa = app(MfaEnforcementService::class);

        $this->assertTrue($legal->hasPermission('contracts.approve'));
        $this->assertTrue($housing->hasPermission('allocations.approve'));
        $this->assertTrue($inspection->hasPermission('inspections.approve'));
        $this->assertTrue($support->hasPermission('support.update'));

        $this->assertFalse($legal->hasPermission('scoring.update'));
        $this->assertFalse($housing->hasPermission('roles.assign'));
        $this->assertFalse($inspection->hasPermission('payments.update'));
        $this->assertFalse($support->hasPermission('documents.view'));

        $this->assertTrue($mfa->requiresMfa($legal));
        $this->assertTrue($mfa->requiresMfa($housing));
        $this->assertTrue($mfa->requiresMfa($inspection));
        $this->assertFalse($mfa->requiresMfa($support));
    }

    public function test_qa30_administrator_creates_backoffice_user_with_role_team_mfa_and_audit(): void
    {
        $administrator = $this->userWithRole('administrator');
        $team = MunicipalTeam::factory()->create(['name' => 'Gabinete Jurídico QA30']);

        $response = $this
            ->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.users.store'), [
                'name' => 'Jurista QA30',
                'email' => 'jurista-qa30@example.test',
                'role' => 'legal_manager',
                'team_id' => $team->id,
                'role_in_team' => 'Coordenação jurídica',
                'status' => 'active',
                'justification' => 'Criação de perfil institucional QA30.',
            ]);

        $user = User::query()->where('email', 'jurista-qa30@example.test')->firstOrFail();

        $response->assertRedirect(route('backoffice.users.show', $user));
        $this->assertTrue($user->hasRole('legal_manager'));
        $this->assertTrue($user->mfa_required);
        $this->assertTrue($user->municipalTeams()->whereKey($team->id)->exists());

        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'user_created',
            'actor_id' => $administrator->id,
            'target_user_id' => $user->id,
            'municipal_team_id' => $team->id,
        ]);
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'team_member_added',
            'target_user_id' => $user->id,
            'municipal_team_id' => $team->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'user_created',
            'subject_user_id' => $user->id,
        ]);
    }

    public function test_qa30_access_change_events_are_immutable(): void
    {
        $event = AccessChangeEvent::factory()->create(['event_code' => 'role_assigned']);

        $this->assertFalse($event->update(['event_code' => 'role_removed']));
        $this->assertFalse($event->delete());
        $this->assertDatabaseHas('access_change_events', [
            'id' => $event->id,
            'event_code' => 'role_assigned',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $this->assertTrue(Role::query()->where('name', $role)->exists());

        $user = User::factory()->create(['email' => $role.'-qa30-'.fake()->unique()->numerify('####').'@example.test']);
        $user->assignRole($role);

        return $user;
    }
}
