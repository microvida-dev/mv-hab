<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\EligibilityCheck;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseNextActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_pending_documents_suggest_document_validation(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();
        DocumentSubmission::factory()->create([
            'application_id' => $application->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Validar documentos')
            ->assertSee('Existem documentos em falta, submetidos ou a rever.');
    }

    public function test_missing_eligibility_suggests_eligibility_without_changing_data(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Executar elegibilidade');

        $this->assertDatabaseMissing('eligibility_checks', [
            'application_id' => $application->id,
        ]);
    }

    public function test_existing_eligibility_moves_next_action_to_scoring(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();
        EligibilityCheck::factory()->create([
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
        ]);

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Rever pontuação');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
