<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\LotteryDrawStatus;
use App\Models\LotteryDraw;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\LotteryTimelineProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class LotteryTimelineProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_returns_no_events_without_permission(): void
    {
        LotteryDraw::factory()->create([
            'status' => LotteryDrawStatus::Ready,
            'scheduled_at' => now()->addDay(),
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('allocations.view')
            ->once()
            ->andReturnFalse();

        $events = (new LotteryTimelineProvider)->forUser($user);

        $this->assertSame([], $events);
    }

    public function test_builds_scheduled_and_ready_events(): void
    {
        $scheduledAt = now()->addDays(2)->startOfMinute();

        LotteryDraw::factory()->create([
            'status' => LotteryDrawStatus::Ready,
            'scheduled_at' => $scheduledAt,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('allocations.view')
            ->once()
            ->andReturnTrue();

        $events = (new LotteryTimelineProvider)->forUser($user);

        $this->assertCount(2, $events);

        $types = collect($events)->pluck('type')->all();

        $this->assertContains(TimelineType::LotteryScheduled, $types);
        $this->assertContains(TimelineType::LotteryReady, $types);

        $scheduled = collect($events)->firstWhere('type', TimelineType::LotteryScheduled);

        $this->assertSame(TimelinePriority::Medium, $scheduled->priority);
        $this->assertSame(TimelineWorkspace::Contests, $scheduled->workspace);
        $this->assertSame('Sorteio agendado', $scheduled->title);
        $this->assertSame($scheduledAt->toIso8601String(), $scheduled->datetime->toIso8601String());
    }

    public function test_builds_completed_and_validated_events(): void
    {
        LotteryDraw::factory()->create([
            'status' => LotteryDrawStatus::Validated,
            'scheduled_at' => now()->subDays(3),
            'completed_at' => now()->subDay()->startOfMinute(),
            'validated_at' => now()->startOfMinute(),
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('allocations.view')
            ->once()
            ->andReturnTrue();

        $events = (new LotteryTimelineProvider)->forUser($user);

        $types = collect($events)->pluck('type')->all();

        $this->assertContains(TimelineType::LotteryScheduled, $types);
        $this->assertContains(TimelineType::LotteryCompleted, $types);
        $this->assertContains(TimelineType::LotteryValidated, $types);
    }
}
