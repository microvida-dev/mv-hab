<?php

namespace App\Services\InternalAlerts;

use App\Enums\InternalAlertSeverity;
use App\Enums\InternalAlertStatus;
use App\Enums\InternalAlertType;
use App\Models\InternalAlert;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Model;

class InternalAlertService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        InternalAlertType $type,
        InternalAlertSeverity $severity,
        string $title,
        string $message,
        ?Model $related = null,
        array $data = [],
        ?User $actor = null,
    ): InternalAlert {
        $query = InternalAlert::query()
            ->where('type', $type->value)
            ->whereIn('status', [InternalAlertStatus::Open->value, InternalAlertStatus::Seen->value, InternalAlertStatus::InProgress->value]);

        if ($related !== null) {
            $query->where('related_type', $related->getMorphClass())->where('related_id', $related->getKey());
        }

        $existing = $query->first();

        if ($existing instanceof InternalAlert) {
            return $existing;
        }

        $alert = new InternalAlert([
            'title' => $title,
            'message' => $message,
            'assigned_role' => $data['assigned_role'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
        $alert->forceFill([
            'alert_number' => $this->number(),
            'type' => $type,
            'severity' => $severity,
            'status' => InternalAlertStatus::Open,
            'assigned_to' => $data['assigned_to'] ?? null,
            'municipality_id' => $data['municipality_id'] ?? null,
            'program_id' => $data['program_id'] ?? null,
            'contest_id' => $data['contest_id'] ?? null,
            'application_id' => $data['application_id'] ?? null,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'created_by' => $actor?->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $alert, 'backoffice', 'internal_alert_create', 'Alerta interno criado.');

        return $alert->refresh();
    }

    public function markSeen(InternalAlert $alert, User $actor): InternalAlert
    {
        if ($alert->seen_at === null) {
            $alert->forceFill([
                'status' => InternalAlertStatus::Seen,
                'seen_at' => now(),
            ])->save();
        }

        return $alert->refresh();
    }

    public function resolve(InternalAlert $alert, User $actor): InternalAlert
    {
        $alert->forceFill([
            'status' => InternalAlertStatus::Resolved,
            'resolved_at' => now(),
            'resolved_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $alert, 'backoffice', 'internal_alert_resolve', 'Alerta interno resolvido.');

        return $alert->refresh();
    }

    public function dismiss(InternalAlert $alert, User $actor): InternalAlert
    {
        $alert->forceFill([
            'status' => InternalAlertStatus::Dismissed,
            'resolved_at' => now(),
            'resolved_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $alert, 'backoffice', 'internal_alert_dismiss', 'Alerta interno dispensado.');

        return $alert->refresh();
    }

    private function number(): string
    {
        $next = InternalAlert::withTrashed()->count() + 1;

        do {
            $number = 'ALR-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (InternalAlert::withTrashed()->where('alert_number', $number)->exists());

        return $number;
    }
}
