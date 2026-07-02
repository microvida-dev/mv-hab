<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\TenantCommunicationStatus;
use App\Enums\TenantInvoiceStatus;
use App\Enums\TenantPaymentStatus;
use App\Enums\TenantTransitionStatus;
use App\Models\TenantCommunication;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\TenantTransition;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\TenantOperationsTimelineProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class TenantOperationsTimelineProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_returns_no_events_without_permission(): void
    {
        TenantTransition::factory()->create();

        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('tenant_operations.view')
            ->once()
            ->andReturnFalse();

        $events = (new TenantOperationsTimelineProvider())->forUser($user);

        $this->assertSame([], $events);
    }

    public function test_builds_transition_pending_event(): void
    {
        TenantTransition::factory()->create([
            'status' => TenantTransitionStatus::Pending,
        ]);

        $events = (new TenantOperationsTimelineProvider())->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::TenantTransitionPending, $events[0]->type);
        $this->assertSame(TimelinePriority::Medium, $events[0]->priority);
        $this->assertSame(TimelineWorkspace::Tenant, $events[0]->workspace);
        $this->assertSame('Transição para inquilino pendente', $events[0]->title);
    }

    public function test_builds_transition_completed_event(): void
    {
        TenantTransition::factory()->create([
            'status' => TenantTransitionStatus::Completed,
            'completed_at' => now()->startOfMinute(),
        ]);

        $events = (new TenantOperationsTimelineProvider())->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::TenantTransitionCompleted, $events[0]->type);
        $this->assertSame(TimelinePriority::Low, $events[0]->priority);
        $this->assertSame('Transição para inquilino concluída', $events[0]->title);
    }

    public function test_builds_invoice_due_event(): void
    {
        $dueDate = now()->addDays(5)->startOfDay();

        TenantInvoice::factory()->create([
            'status' => TenantInvoiceStatus::Issued,
            'due_date' => $dueDate->toDateString(),
        ]);

        $events = (new TenantOperationsTimelineProvider())->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::TenantInvoiceDue, $events[0]->type);
        $this->assertSame(TimelinePriority::Medium, $events[0]->priority);
        $this->assertSame('Fatura de inquilino a vencer', $events[0]->title);
        $this->assertSame($dueDate->toIso8601String(), $events[0]->datetime->toIso8601String());
    }

    public function test_builds_invoice_overdue_event(): void
    {
        TenantInvoice::factory()->create([
            'status' => TenantInvoiceStatus::Overdue,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $events = (new TenantOperationsTimelineProvider())->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::TenantInvoiceOverdue, $events[0]->type);
        $this->assertSame(TimelinePriority::Critical, $events[0]->priority);
        $this->assertSame('Fatura de inquilino vencida', $events[0]->title);
    }

    public function test_builds_payment_registered_event(): void
    {
        TenantPayment::factory()->create([
            'status' => TenantPaymentStatus::Registered,
            'registered_at' => now()->startOfMinute(),
        ]);

        $events = (new TenantOperationsTimelineProvider())->forUser($this->authorizedUser());

        $event = collect($events)->firstWhere('type', TimelineType::TenantPaymentRegistered);

        $this->assertNotNull($event);
        $this->assertSame(TimelinePriority::Medium, $event->priority);
        $this->assertSame('Pagamento de inquilino registado', $event->title);
    }

    public function test_builds_payment_confirmed_event(): void
    {
        TenantPayment::factory()->create([
            'status' => TenantPaymentStatus::Confirmed,
            'confirmed_at' => now()->startOfMinute(),
        ]);

        $events = (new TenantOperationsTimelineProvider())->forUser($this->authorizedUser());

        $event = collect($events)->firstWhere('type', TimelineType::TenantPaymentConfirmed);

        $this->assertNotNull($event);
        $this->assertSame(TimelinePriority::Low, $event->priority);
        $this->assertSame('Pagamento de inquilino confirmado', $event->title);
    }

    public function test_builds_open_communication_event(): void
    {
        TenantCommunication::factory()->create([
            'status' => TenantCommunicationStatus::Open,
            'last_message_at' => now()->startOfMinute(),
        ]);

        $events = (new TenantOperationsTimelineProvider())->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::TenantCommunicationOpen, $events[0]->type);
        $this->assertSame(TimelinePriority::Medium, $events[0]->priority);
        $this->assertSame('Comunicação de inquilino aberta', $events[0]->title);
    }

    public function test_builds_awaiting_municipality_event(): void
    {
        TenantCommunication::factory()->create([
            'status' => TenantCommunicationStatus::AwaitingMunicipality,
            'last_message_at' => now()->startOfMinute(),
        ]);

        $events = (new TenantOperationsTimelineProvider())->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::TenantCommunicationAwaitingMunicipality, $events[0]->type);
        $this->assertSame(TimelinePriority::High, $events[0]->priority);
        $this->assertSame('Comunicação aguarda resposta do município', $events[0]->title);
    }

    private function authorizedUser(): User
    {
        /** @var User $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->with('tenant_operations.view')
            ->once()
            ->andReturnTrue();

        return $user;
    }
}
