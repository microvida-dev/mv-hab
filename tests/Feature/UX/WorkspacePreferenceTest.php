<?php

namespace Tests\Feature\UX;

use App\Models\User;
use App\Models\UserWorkspacePreference;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspacePreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_user_can_set_authorized_preferred_workspace(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->put(route('navigation.workspace-preferences.update'), [
                'preferred_workspace' => 'concursos',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_workspace_preferences', [
            'user_id' => $administrator->id,
            'preferred_workspace' => 'concursos',
        ]);
    }

    public function test_unauthorized_preferred_workspace_is_ignored(): void
    {
        $supportAgent = $this->userWithRole('support_agent');

        $this->actingAs($supportAgent)
            ->withSession(['mfa.verified_at' => now()])
            ->put(route('navigation.workspace-preferences.update'), [
                'preferred_workspace' => 'administracao',
            ])
            ->assertRedirect();

        $preference = UserWorkspacePreference::query()
            ->where('user_id', $supportAgent->id)
            ->first();

        $this->assertNotNull($preference);
        $this->assertNull($preference->preferred_workspace);
    }

    public function test_workspace_preference_payload_is_available_on_workspace_page(): void
    {
        $administrator = $this->userWithRole('administrator');

        UserWorkspacePreference::query()->create([
            'user_id' => $administrator->id,
            'preferred_workspace' => 'concursos',
            'collapsed_groups' => [],
            'hidden_modules' => [],
            'dashboard_layout' => [],
            'workspace_layout' => [],
            'settings' => [],
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('workspaces.show', 'concursos'))
            ->assertOk()
            ->assertSee('Espaço inicial');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    public function test_dashboard_highlights_preferred_workspace(): void
    {
        $administrator = $this->userWithRole('administrator');

        UserWorkspacePreference::query()->create([
            'user_id' => $administrator->id,
            'preferred_workspace' => 'concursos',
            'collapsed_groups' => [],
            'hidden_modules' => [],
            'dashboard_layout' => [],
            'workspace_layout' => [],
            'settings' => [],
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Workspace recomendado: Concursos');
    }
}
