<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\Contest;
use App\Models\DocumentSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class CaseWorkspaceAuthorizationTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_candidate_cannot_access_backoffice_enterprise_case_workspace(): void
    {
        $contest = Contest::factory()->open()->create();

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.cases.contests.show', $contest))
            ->assertForbidden();
    }

    public function test_candidate_cannot_access_backoffice_application_case_workspace(): void
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

    public function test_auditor_sees_read_only_case_workspace(): void
    {
        $contest = Contest::factory()->open()->create();

        $this->actingAs($this->userWithRole('auditor'))
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.contests.show', $contest))
            ->assertOk()
            ->assertSee('Perfil de consulta sem ações mutáveis')
            ->assertDontSee('Eliminar')
            ->assertDontSee('Aprovar');
    }

    public function test_auditor_sees_read_only_application_case_without_mutable_next_action(): void
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
}
