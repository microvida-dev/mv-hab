<?php

namespace Tests\Feature\UX;

use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class SmartQueueTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_smart_queue_derives_urgent_and_overdue_queues_from_existing_fields(): void
    {
        $administrator = $this->backofficeUser();
        WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-URGENT',
            'priority' => WorkTask::PRIORITY_URGENT,
            'due_at' => now()->addHour(),
        ]);
        WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-OVERDUE',
            'status' => WorkTask::STATUS_OVERDUE,
            'due_at' => now()->subDay(),
        ]);

        $this->actingAs($administrator)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertOk()
            ->assertSee('Filas inteligentes')
            ->assertSee('Urgente')
            ->assertSee('Em atraso')
            ->assertSee('WTK-UX06-URGENT')
            ->assertSee('WTK-UX06-OVERDUE');
    }
}
