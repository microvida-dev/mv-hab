<?php

namespace Tests\Unit\Productivity;

use App\Models\WorkTask;
use App\Services\Productivity\SmartQueueService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class SmartQueueServiceTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_builds_urgent_queue_from_existing_priority(): void
    {
        $administrator = $this->backofficeUser();
        WorkTask::factory()->assigned($administrator)->create([
            'priority' => WorkTask::PRIORITY_URGENT,
        ]);

        $queues = collect(app(SmartQueueService::class)->forUser($administrator))->keyBy('key');

        $this->assertArrayHasKey('urgent', $queues->all());
        $this->assertNotEmpty($queues->get('urgent')['items']);
    }
}
