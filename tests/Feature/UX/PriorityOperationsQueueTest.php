<?php

namespace Tests\Feature\UX;

use App\Models\DocumentSubmission;
use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityOperationsQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_dashboard_renders_priority_operations_queue_for_technician(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $team = MunicipalTeam::factory()->create(['name' => 'Gabinete Técnico']);
        $technician->municipalTeams()->attach($team->id, ['joined_at' => now()]);

        WorkTask::factory()
            ->assigned($technician)
            ->create([
                'municipal_team_id' => $team->id,
                'due_at' => now()->subDay(),
            ]);

        DocumentSubmission::factory()->create(['status' => 'submitted']);

        $this->actingAs($technician)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Fila operacional prioritária')
            ->assertSee('Tarefas vencidas')
            ->assertSee('Documentos pendentes')
            ->assertSee('Abrir prioridade');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
