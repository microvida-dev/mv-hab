<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\ProcessTimelineEvent;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_timeline_aggregates_process_events_and_work_tasks_without_sensitive_paths(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();

        ProcessTimelineEvent::factory()->create([
            'application_id' => $application->id,
            'title' => 'Documento recebido',
            'description' => 'Identificador 123456789 em storage/app/private/documento.pdf',
            'occurred_at' => now()->subDay(),
        ]);

        WorkTask::factory()->create([
            'related_type' => $application::class,
            'related_id' => $application->id,
            'status' => WorkTask::STATUS_PENDING,
        ]);

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Documento recebido')
            ->assertSee('Tarefa')
            ->assertDontSee('123456789')
            ->assertDontSee('storage/app/private');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
