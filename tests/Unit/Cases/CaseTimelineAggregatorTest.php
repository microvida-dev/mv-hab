<?php

namespace Tests\Unit\Cases;

use App\Models\Contest;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Cases\CaseTimelineAggregator;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseTimelineAggregatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_timeline_includes_work_task_events_when_authorized(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');
        $contest = Contest::factory()->open()->create();
        WorkTask::factory()->create(['related_type' => $contest::class, 'related_id' => $contest->id]);

        $timeline = app(CaseTimelineAggregator::class)->forCase($user, $contest);

        $this->assertNotEmpty($timeline);
        $this->assertTrue(collect($timeline)->contains(fn ($item): bool => $item->type === 'work_task'));
    }
}
