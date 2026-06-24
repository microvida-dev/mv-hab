<?php

namespace Tests\Feature\Backoffice;

use App\Models\User;
use App\Models\WorkTask;
use App\Services\Workflows\WorkTaskSlaService;
use Carbon\Carbon;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkTaskSlaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sla_due_date_uses_business_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-26 10:00:00'));

        $dueAt = app(WorkTaskSlaService::class)->calculateDueAt(WorkTask::TYPE_DOCUMENT_REVIEW);

        $this->assertSame('2026-07-03 10:00:00', $dueAt->toDateTimeString());
    }

    public function test_overdue_job_marks_only_active_expired_tasks(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));
        $actor = User::factory()->create();
        $expired = WorkTask::factory()->create([
            'status' => WorkTask::STATUS_ASSIGNED,
            'due_at' => now()->subDay(),
        ]);
        $completed = WorkTask::factory()->create([
            'status' => WorkTask::STATUS_COMPLETED,
            'due_at' => now()->subDay(),
            'completed_at' => now()->subHour(),
        ]);

        $count = app(WorkTaskSlaService::class)->markOverdue($actor);

        $this->assertSame(1, $count);
        $this->assertSame(WorkTask::STATUS_OVERDUE, $expired->refresh()->status);
        $this->assertSame(WorkTask::STATUS_COMPLETED, $completed->refresh()->status);
        $this->assertDatabaseHas('work_task_histories', ['work_task_id' => $expired->id, 'event_code' => 'work_task_overdue']);
    }
}
