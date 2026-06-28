<?php

namespace Tests\Feature\UX;

use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class DeadlineIndicatorTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_deadline_indicators_show_overdue_due_soon_and_no_deadline(): void
    {
        $administrator = $this->backofficeUser();
        WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-LATE',
            'status' => WorkTask::STATUS_OVERDUE,
            'due_at' => now()->subDay(),
        ]);
        WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-SOON',
            'due_at' => now()->addDay(),
        ]);
        WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-NODATE',
            'due_at' => null,
        ]);

        $this->actingAs($administrator)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertOk()
            ->assertSee('Em atraso')
            ->assertSee('A vencer')
            ->assertSee('Sem prazo');
    }
}
