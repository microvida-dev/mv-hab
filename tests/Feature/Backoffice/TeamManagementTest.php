<?php

namespace Tests\Feature\Backoffice;

use App\Models\MunicipalTeam;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_administrator_creates_and_updates_municipal_team(): void
    {
        $administrator = $this->userWithRole('administrator');

        $response = $this
            ->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.teams.store'), [
                'name' => 'Atendimento QA30',
                'description' => 'Equipa de atendimento QA30',
                'status' => 'active',
                'functional_scopes' => 'support, candidate_experience',
                'justification' => 'Criação de equipa QA30.',
            ]);

        $team = MunicipalTeam::query()->where('name', 'Atendimento QA30')->firstOrFail();

        $response->assertRedirect(route('backoffice.teams.show', $team));
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'team_created',
            'municipal_team_id' => $team->id,
        ]);

        $this
            ->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('backoffice.teams.update', $team), [
                'name' => 'Atendimento QA30 Revisto',
                'description' => 'Equipa revista',
                'status' => 'active',
                'functional_scopes' => 'support',
                'justification' => 'Atualização de equipa QA30.',
            ])
            ->assertRedirect(route('backoffice.teams.show', $team));

        $this->assertDatabaseHas('municipal_teams', ['id' => $team->id, 'name' => 'Atendimento QA30 Revisto']);
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'team_updated',
            'municipal_team_id' => $team->id,
        ]);
    }

    public function test_administrator_adds_and_removes_team_member_with_audit(): void
    {
        $administrator = $this->userWithRole('administrator');
        $member = $this->userWithRole('support_agent');
        $team = MunicipalTeam::factory()->create(['name' => 'Atendimento Membros QA30']);

        $this
            ->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.teams.members.store', $team), [
                'user_id' => $member->id,
                'role_in_team' => 'Atendimento',
                'justification' => 'Associação à equipa QA30.',
            ])
            ->assertRedirect();

        $this->assertTrue($team->members()->whereKey($member->id)->exists());
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'team_member_added',
            'target_user_id' => $member->id,
            'municipal_team_id' => $team->id,
        ]);

        $this
            ->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.teams.members.remove', $team), [
                'user_id' => $member->id,
                'justification' => 'Remoção da equipa QA30.',
            ])
            ->assertRedirect();

        $this->assertFalse($team->members()->whereKey($member->id)->exists());
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'team_member_removed',
            'target_user_id' => $member->id,
            'municipal_team_id' => $team->id,
        ]);
    }

    public function test_inactive_team_cannot_receive_new_member(): void
    {
        $administrator = $this->userWithRole('administrator');
        $member = $this->userWithRole('support_agent');
        $team = MunicipalTeam::factory()->inactive()->create(['name' => 'Equipa Inativa QA30']);

        $this
            ->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->from(route('backoffice.teams.show', $team))
            ->post(route('backoffice.teams.members.store', $team), [
                'user_id' => $member->id,
                'justification' => 'Tentativa de associação a equipa inativa.',
            ])
            ->assertRedirect(route('backoffice.teams.show', $team))
            ->assertSessionHasErrors('access');

        $this->assertFalse($team->members()->whereKey($member->id)->exists());
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['email' => $role.'-teams-qa30-'.fake()->unique()->numerify('####').'@example.test']);
        $user->assignRole($role);

        return $user;
    }
}
