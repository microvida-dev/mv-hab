<?php

namespace App\Services\KeyHandover;

use App\Models\KeyHandoverAppointment;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class KeyHandoverNotificationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function recordInternalNotification(KeyHandoverAppointment $appointment): void
    {
        $this->audit->record(AuditEvents::CREATE, $appointment, 'communications', 'key_handover_internal_notification', 'Notificação interna de entrega de chaves registada.');
    }
}
