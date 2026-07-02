<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Enums\AllocationStatus;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\Allocation;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\AllocationTimelineProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class AllocationTimelineProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_returns_no_events_without_permission(): void
    {
        Allocation::factory()->create([
            'status' => AllocationStatus::Offered,
            'offered_at' => now()->addDay(),
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('allocations.view')
            ->once()
            ->andReturnFalse();

        $events = (new AllocationTimelineProvider())->forUser($user);

        $this->assertSame([], $events);
    }

    public function test_builds_offer_event(): void
    {
        $deadline = now()->addDays(3)->startOfMinute();

        Allocation::factory()->create([
            'status' => AllocationStatus::Offered,
            'offered_at' => now()->startOfMinute(),
            'acceptance_deadline_at' => $deadline,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('allocations.view')
            ->once()
            ->andReturnTrue();

        $events = (new AllocationTimelineProvider())->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::AllocationOffer, $events[0]->type);
        $this->assertSame(TimelinePriority::Medium, $events[0]->priority);
        $this->assertSame(TimelineWorkspace::Applications, $events[0]->workspace);
        $this->assertSame('Oferta de atribuição emitida', $events[0]->title);
        $this->assertSame($deadline->toIso8601String(), $events[0]->datetime->toIso8601String());
    }

    public function test_builds_accepted_and_ready_for_contract_events(): void
    {
        Allocation::factory()->create([
            'status' => AllocationStatus::ReadyForContract,
            'offered_at' => now()->subDays(3),
            'accepted_at' => now()->subDay(),
            'ready_for_contract_at' => now()->addDay(),
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('allocations.view')
            ->once()
            ->andReturnTrue();

        $events = (new AllocationTimelineProvider())->forUser($user);

        $this->assertCount(3, $events);

        $this->assertContains(
            TimelineType::AllocationOffer,
            collect($events)->pluck('type')->all(),
        );

        $this->assertContains(
            TimelineType::AllocationAccepted,
            collect($events)->pluck('type')->all(),
        );

        $this->assertContains(
            TimelineType::AllocationReadyForContract,
            collect($events)->pluck('type')->all(),
        );
    }
}
