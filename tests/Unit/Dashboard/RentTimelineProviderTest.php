<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\LeasePaymentStatus;
use App\Enums\RentInstallmentStatus;
use App\Models\LeasePayment;
use App\Models\RentInstallment;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\RentTimelineProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class RentTimelineProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_returns_no_events_without_permission(): void
    {
        RentInstallment::factory()->create([
            'status' => RentInstallmentStatus::Issued,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('finance.view')
            ->once()
            ->andReturnFalse();

        $events = (new RentTimelineProvider)->forUser($user);

        $this->assertSame([], $events);
    }

    public function test_builds_rent_due_event(): void
    {
        $dueDate = now()->addDays(5)->startOfDay();

        RentInstallment::factory()->create([
            'status' => RentInstallmentStatus::Issued,
            'due_date' => $dueDate->toDateString(),
            'amount_due' => 350,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('finance.view')
            ->once()
            ->andReturnTrue();

        $events = (new RentTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::RentDue, $events[0]->type);
        $this->assertSame(TimelinePriority::Medium, $events[0]->priority);
        $this->assertSame(TimelineWorkspace::Finance, $events[0]->workspace);
        $this->assertSame('Prestação de renda a vencer', $events[0]->title);
        $this->assertSame(RentInstallmentStatus::Issued->value, $events[0]->metadata['status']);
        $this->assertSame($dueDate->toIso8601String(), $events[0]->datetime->toIso8601String());
    }

    public function test_builds_overdue_rent_event(): void
    {
        $overdueAt = now()->subDay()->startOfMinute();

        RentInstallment::factory()->create([
            'status' => RentInstallmentStatus::Overdue,
            'due_date' => now()->subDays(5)->toDateString(),
            'overdue_at' => $overdueAt,
            'amount_due' => 350,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('finance.view')
            ->once()
            ->andReturnTrue();

        $events = (new RentTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::RentOverdue, $events[0]->type);
        $this->assertSame(TimelinePriority::Critical, $events[0]->priority);
        $this->assertSame('Prestação de renda em atraso', $events[0]->title);
        $this->assertSame($overdueAt->toIso8601String(), $events[0]->datetime->toIso8601String());
    }

    public function test_builds_payment_received_event(): void
    {
        $receivedAt = now()->startOfMinute();

        LeasePayment::factory()->create([
            'status' => LeasePaymentStatus::Confirmed,
            'received_at' => $receivedAt,
            'amount' => 350,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('finance.view')
            ->once()
            ->andReturnTrue();

        $events = (new RentTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::LeasePaymentReceived, $events[0]->type);
        $this->assertSame(TimelinePriority::Low, $events[0]->priority);
        $this->assertSame(TimelineWorkspace::Finance, $events[0]->workspace);
        $this->assertSame('Pagamento de renda recebido', $events[0]->title);
        $this->assertSame($receivedAt->toIso8601String(), $events[0]->datetime->toIso8601String());
    }
}
