<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseWorkspaceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_cannot_access_backoffice_case_workspace(): void
    {
        $candidate = $this->userWithRole('candidate');
        $application = Application::factory()->submitted()->create(['user_id' => $candidate->id]);

        $response = $this->actingAs($candidate)
            ->get(route('backoffice.cases.applications.show', $application));

        $this->assertContains($response->getStatusCode(), [302, 403]);
    }

    public function test_user_without_application_permission_is_forbidden(): void
    {
        $supportAgent = $this->userWithRole('support_agent');
        $application = Application::factory()->submitted()->create();

        $this->actingAs($supportAgent)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertForbidden();
    }

    public function test_auditor_sees_read_only_case_without_mutable_next_action(): void
    {
        $auditor = $this->userWithRole('auditor');
        $application = Application::factory()->submitted()->create();
        DocumentSubmission::factory()->create([
            'application_id' => $application->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Auditoria')
            ->assertSee('Perfil de auditoria')
            ->assertDontSee('Abrir ação');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
