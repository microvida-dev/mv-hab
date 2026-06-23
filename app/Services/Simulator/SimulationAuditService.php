<?php

namespace App\Services\Simulator;

use App\Models\SimulationSession;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class SimulationAuditService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function record(User $user, SimulationSession $session, string $action, string $description): void
    {
        $this->auditLogger->record(
            event: $action === 'create' ? AuditEvents::CREATE : AuditEvents::UPDATE,
            auditable: $session,
            module: 'simulator',
            action: $action,
            description: $description,
            metadata: ['actor_id' => $user->id],
        );
    }
}
