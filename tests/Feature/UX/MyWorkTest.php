<?php

namespace Tests\Feature\UX;

use App\Models\MunicipalTeam;
use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class MyWorkTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_my_work_groups_assigned_and_team_items(): void
    {
        $team = MunicipalTeam::factory()->create(['name' => 'Equipa UX06']);
        $technician = $this->backofficeUser('municipal_technician', $team, 'Técnica UX06');

        WorkTask::factory()->assigned($technician)->create([
            'task_number' => 'WTK-UX06-MY',
            'municipal_team_id' => $team->id,
        ]);
        WorkTask::factory()->create([
            'task_number' => 'WTK-UX06-TEAM',
            'municipal_team_id' => $team->id,
            'assigned_user_id' => null,
            'status' => WorkTask::STATUS_PENDING,
        ]);

        $this->actingAs($technician)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertOk()
            ->assertSee('O Meu Trabalho')
            ->assertSee('Atribuído a mim')
            ->assertSee('Fila da minha equipa')
            ->assertSee('WTK-UX06-MY')
            ->assertSee('WTK-UX06-TEAM');
    }
}
