<?php

namespace Tests\Unit\Productivity;

use App\Models\WorkTask;
use App\Services\Productivity\NextCaseService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class NextCaseServiceTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_recommends_highest_priority_authorized_task_without_mutation(): void
    {
        $administrator = $this->backofficeUser();
        WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-FUTURE',
            'priority' => WorkTask::PRIORITY_LOW,
            'due_at' => now()->addWeek(),
        ]);
        $overdue = WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-PRIORITY',
            'status' => WorkTask::STATUS_OVERDUE,
            'priority' => WorkTask::PRIORITY_URGENT,
            'due_at' => now()->subDay(),
        ]);

        $next = app(NextCaseService::class)->forUser($administrator);

        $this->assertNotNull($next);
        $this->assertStringContainsString('WTK-UX06-PRIORITY', $next['title']);
        $this->assertDatabaseHas('work_tasks', ['id' => $overdue->id, 'status' => WorkTask::STATUS_OVERDUE]);
    }
}
