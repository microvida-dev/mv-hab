<?php

namespace App\Services\Convocations;

use App\Models\DrawConvocation;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class ConvocationNotificationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function recordInternalNotification(DrawConvocation $convocation): void
    {
        $this->audit->record(AuditEvents::CREATE, $convocation, 'communications', 'draw_convocation_internal_notification', 'Notificação interna de convocatória registada.');
    }
}
