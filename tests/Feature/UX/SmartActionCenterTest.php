<?php

namespace Tests\Feature\UX;

use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class SmartActionCenterTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_dashboard_shows_authorized_smart_action_center_without_changing_tasks(): void
    {
        $administrator = $this->backofficeUser();
        $task = WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-HOJE',
            'status' => WorkTask::STATUS_ASSIGNED,
            'priority' => WorkTask::PRIORITY_HIGH,
            'due_at' => now()->endOfDay(),
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Centro de Trabalho')
            ->assertSee('Hoje')
            ->assertSee('WTK-UX06-HOJE')
            ->assertSee('Abrir produtividade');

        $this->assertDatabaseHas('work_tasks', [
            'id' => $task->id,
            'status' => WorkTask::STATUS_ASSIGNED,
        ]);
    }
}
