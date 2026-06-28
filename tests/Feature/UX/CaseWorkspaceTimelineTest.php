<?php

namespace Tests\Feature\UX;

use App\Models\Contest;
use App\Models\WorkTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class CaseWorkspaceTimelineTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_timeline_aggregates_authorized_work_tasks(): void
    {
        $contest = Contest::factory()->open()->create();
        WorkTask::factory()->create([
            'related_type' => $contest::class,
            'related_id' => $contest->id,
            'type' => WorkTask::TYPE_COMPLAINT_REVIEW,
        ]);

        $this->actingAs($this->userWithRole())
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.contests.show', $contest))
            ->assertOk()
            ->assertSee('Cronologia agregada')
            ->assertSee('Tarefa: Reclamação');
    }
}
