<?php

namespace Tests\Unit\Productivity;

use App\Models\WorkTask;
use App\Services\Productivity\SmartActionCenterService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class SmartActionCenterServiceTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_groups_visible_work_by_deadline_sections(): void
    {
        $administrator = $this->backofficeUser();
        WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-SVC-TODAY',
            'due_at' => now()->endOfDay(),
        ]);

        $sections = collect(app(SmartActionCenterService::class)->forUser($administrator))->keyBy('key');

        $this->assertArrayHasKey('today', $sections->all());
        $this->assertSame('WTK-UX06-SVC-TODAY', str($sections->get('today')['items'][0]['title'])->afterLast('· ')->toString());
    }
}
