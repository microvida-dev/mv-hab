<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Enums\ContractStatus;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\Contract;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\ContractTimelineProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class ContractTimelineProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_returns_no_events_without_permission(): void
    {
        Contract::factory()->create([
            'status' => ContractStatus::Issued,
            'issued_at' => now(),
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('contracts.view')
            ->once()
            ->andReturnFalse();

        $events = (new ContractTimelineProvider)->forUser($user);

        $this->assertSame([], $events);
    }

    public function test_builds_issued_event(): void
    {
        $issuedAt = now()->startOfMinute();

        Contract::factory()->create([
            'status' => ContractStatus::Issued,
            'issued_at' => $issuedAt,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('contracts.view')
            ->once()
            ->andReturnTrue();

        $events = (new ContractTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::ContractIssued, $events[0]->type);
        $this->assertSame(TimelinePriority::Medium, $events[0]->priority);
        $this->assertSame(TimelineWorkspace::Tenant, $events[0]->workspace);
        $this->assertSame('Contrato emitido', $events[0]->title);
        $this->assertSame($issuedAt->toIso8601String(), $events[0]->datetime->toIso8601String());
    }

    public function test_builds_signed_event(): void
    {
        $signedAt = now()->startOfMinute();

        Contract::factory()->create([
            'status' => ContractStatus::Signed,
            'signed_at' => $signedAt,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('contracts.view')
            ->once()
            ->andReturnTrue();

        $events = (new ContractTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::ContractSigned, $events[0]->type);
        $this->assertSame(TimelinePriority::Medium, $events[0]->priority);
        $this->assertSame('Contrato assinado', $events[0]->title);
    }

    public function test_builds_active_event(): void
    {
        $activatedAt = now()->startOfMinute();

        Contract::factory()->create([
            'status' => ContractStatus::Active,
            'activated_at' => $activatedAt,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('contracts.view')
            ->once()
            ->andReturnTrue();

        $events = (new ContractTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::ContractActive, $events[0]->type);
        $this->assertSame(TimelinePriority::Low, $events[0]->priority);
        $this->assertSame('Contrato ativo', $events[0]->title);
    }

    public function test_builds_suspended_event(): void
    {
        $suspendedAt = now()->startOfMinute();

        Contract::factory()->create([
            'status' => ContractStatus::Suspended,
            'suspended_at' => $suspendedAt,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('contracts.view')
            ->once()
            ->andReturnTrue();

        $events = (new ContractTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::ContractSuspended, $events[0]->type);
        $this->assertSame(TimelinePriority::High, $events[0]->priority);
        $this->assertSame('Contrato suspenso', $events[0]->title);
    }

    public function test_builds_terminated_event(): void
    {
        $terminatedAt = now()->startOfMinute();

        Contract::factory()->create([
            'status' => ContractStatus::Terminated,
            'terminated_at' => $terminatedAt,
        ]);

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('contracts.view')
            ->once()
            ->andReturnTrue();

        $events = (new ContractTimelineProvider)->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::ContractTerminated, $events[0]->type);
        $this->assertSame(TimelinePriority::Medium, $events[0]->priority);
        $this->assertSame('Contrato terminado', $events[0]->title);
    }
}
