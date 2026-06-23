<?php

namespace App\Services\Visits;

use App\Models\HousingVisit;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class VisitAuditService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function availability(string $event, VisitAvailability $availability, string $description, array $metadata = []): void
    {
        $this->auditLogger->record(
            $event,
            $availability,
            'visits',
            'visit_availability',
            $description,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function slot(string $event, VisitSlot $slot, string $description, array $metadata = []): void
    {
        $this->auditLogger->record(
            $event,
            $slot,
            'visits',
            'visit_slot',
            $description,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function visit(string $event, HousingVisit $visit, string $description, ?User $actor = null, array $metadata = []): void
    {
        $this->auditLogger->record(
            $event,
            $visit,
            'visits',
            'housing_visit',
            $description,
            metadata: array_filter([
                'actor_id' => $actor?->id,
                ...$metadata,
            ], static fn (mixed $value): bool => $value !== null),
        );
    }

    public function created(HousingVisit $visit, User $actor): void
    {
        $this->visit(AuditEvents::CREATE, $visit, 'Visita habitacional agendada.', $actor);
    }

    public function updated(HousingVisit $visit, User $actor, string $description): void
    {
        $this->visit(AuditEvents::UPDATE, $visit, $description, $actor);
    }
}
