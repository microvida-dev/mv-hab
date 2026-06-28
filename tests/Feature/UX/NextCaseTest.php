<?php

namespace Tests\Feature\UX;

use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class NextCaseTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_next_case_recommends_authorized_item_without_mutating_it(): void
    {
        $administrator = $this->backofficeUser();
        $task = WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-NEXT',
            'status' => WorkTask::STATUS_OVERDUE,
            'priority' => WorkTask::PRIORITY_URGENT,
            'due_at' => now()->subDay(),
        ]);

        $this->actingAs($administrator)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertOk()
            ->assertSee('Próximo processo sugerido')
            ->assertSee('WTK-UX06-NEXT')
            ->assertSee('Abrir processo');

        $this->assertDatabaseHas('work_tasks', [
            'id' => $task->id,
            'status' => WorkTask::STATUS_OVERDUE,
        ]);
    }
}
