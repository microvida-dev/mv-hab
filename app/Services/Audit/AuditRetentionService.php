<?php

namespace App\Services\Audit;

use App\Models\AuditEvent;

class AuditRetentionService
{
    /**
     * @return array{events: int, oldest_event_at: mixed, policy: string}
     */
    public function summary(): array
    {
        return [
            'events' => AuditEvent::query()->count(),
            'oldest_event_at' => AuditEvent::query()->oldest('occurred_at')->value('occurred_at'),
            'policy' => 'Retenção de auditoria sujeita a política municipal aprovada. Não há eliminação automática nesta sprint.',
        ];
    }
}
