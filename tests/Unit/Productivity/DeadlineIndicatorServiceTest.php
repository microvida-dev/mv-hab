<?php

namespace Tests\Unit\Productivity;

use App\Models\WorkTask;
use App\Services\Productivity\DeadlineIndicatorService;
use Tests\TestCase;

class DeadlineIndicatorServiceTest extends TestCase
{
    public function test_maps_deadline_states_without_persisting_data(): void
    {
        $service = app(DeadlineIndicatorService::class);

        $overdue = new WorkTask(['status' => WorkTask::STATUS_ASSIGNED]);
        $overdue->due_at = now()->subDay();

        $soon = new WorkTask(['status' => WorkTask::STATUS_ASSIGNED]);
        $soon->due_at = now()->addDay();

        $withoutDeadline = new WorkTask(['status' => WorkTask::STATUS_ASSIGNED]);

        $this->assertSame('overdue', $service->forWorkTask($overdue)['state']);
        $this->assertSame('warning', $service->forWorkTask($soon)['state']);
        $this->assertSame('neutral', $service->forWorkTask($withoutDeadline)['state']);
    }
}
