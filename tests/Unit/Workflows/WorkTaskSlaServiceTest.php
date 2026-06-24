<?php

namespace Tests\Unit\Workflows;

use App\Models\WorkTask;
use App\Services\Workflows\WorkTaskSlaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkTaskSlaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_workflow_sla_service_returns_expected_default_policies(): void
    {
        $policies = app(WorkTaskSlaService::class)->defaultPolicies();

        $this->assertSame(5, $policies[WorkTask::TYPE_DOCUMENT_REVIEW]['business_days']);
        $this->assertSame(10, $policies[WorkTask::TYPE_COMPLAINT_REVIEW]['business_days']);
        $this->assertSame(15, $policies[WorkTask::TYPE_RGPD_REQUEST]['business_days']);
    }

    public function test_workflow_sla_service_identifies_due_soon_task(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $task = new WorkTask([
            'type' => WorkTask::TYPE_COMPLAINT_REVIEW,
            'status' => WorkTask::STATUS_ASSIGNED,
            'due_at' => now()->addDays(2),
        ]);

        $this->assertTrue(app(WorkTaskSlaService::class)->isDueSoon($task));
    }
}
