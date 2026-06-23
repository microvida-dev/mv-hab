<?php

namespace App\Services\Maintenance;

use App\Enums\MaintenanceRequestStatus;
use App\Enums\MaintenanceUrgency;
use App\Enums\OfficialNotificationType;
use App\Enums\TechnicalHistoryEventType;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Properties\PropertyTechnicalHistoryService;
use App\Support\AuditEvents;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class MaintenanceStatusService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PropertyTechnicalHistoryService $history,
        private readonly MaintenanceNotificationService $notifications,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function review(MaintenanceRequest $request, User $actor, array $data): MaintenanceRequest
    {
        $request->forceFill([
            'technical_priority' => MaintenanceUrgency::from($this->urgencyInput($request, $data['technical_priority'] ?? $data['urgency'] ?? null)),
            'urgency' => MaintenanceUrgency::from($this->urgencyInput($request, $data['urgency'] ?? null)),
            'maintenance_category_id' => $data['maintenance_category_id'] ?? $request->maintenance_category_id,
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => $actor->id,
        ])->save();

        return $this->transition($request, MaintenanceRequestStatus::UnderReview, $actor, $this->stringOrNull($data['review_notes'] ?? null), OfficialNotificationType::MaintenanceRequestUnderReview);
    }

    public function schedule(MaintenanceRequest $request, User $actor, ?string $scheduledFor = null): MaintenanceRequest
    {
        $request->forceFill(['scheduled_for' => $scheduledFor ? Carbon::parse($scheduledFor) : $request->scheduled_for])->save();

        return $this->transition($request, MaintenanceRequestStatus::Scheduled, $actor, null, OfficialNotificationType::MaintenanceRequestScheduled);
    }

    public function start(MaintenanceRequest $request, User $actor): MaintenanceRequest
    {
        return $this->transition($request, MaintenanceRequestStatus::InProgress, $actor, null, OfficialNotificationType::MaintenanceRequestInProgress);
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function resolve(MaintenanceRequest $request, User $actor, array $data): MaintenanceRequest
    {
        $request->forceFill([
            'resolution_summary' => $data['resolution_summary'],
            'closure_notes' => $data['closure_notes'] ?? null,
            'resolved_at' => now(),
        ])->save();

        return $this->transition($request, MaintenanceRequestStatus::Resolved, $actor, $this->stringOrNull($data['resolution_summary'] ?? null), OfficialNotificationType::MaintenanceRequestResolved, visibleToTenant: true);
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function reject(MaintenanceRequest $request, User $actor, array $data): MaintenanceRequest
    {
        $request->forceFill([
            'rejection_reason' => $data['rejection_reason'],
            'resolved_at' => now(),
        ])->save();

        return $this->transition($request, MaintenanceRequestStatus::Rejected, $actor, $this->stringOrNull($data['rejection_reason'] ?? null), OfficialNotificationType::MaintenanceRequestRejected, visibleToTenant: true);
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function close(MaintenanceRequest $request, User $actor, array $data = []): MaintenanceRequest
    {
        if (! $this->requestHasStatus($request, [MaintenanceRequestStatus::Resolved, MaintenanceRequestStatus::Rejected])) {
            throw ValidationException::withMessages(['status' => 'Só é possível fechar pedidos resolvidos ou rejeitados.']);
        }

        $request->forceFill([
            'closure_notes' => $data['closure_notes'] ?? $request->closure_notes,
            'closed_at' => now(),
            'closed_by' => $actor->id,
        ])->save();

        return $this->transition($request, MaintenanceRequestStatus::Closed, $actor, $this->stringOrNull($data['closure_notes'] ?? null), OfficialNotificationType::MaintenanceRequestClosed, visibleToTenant: true);
    }

    public function cancel(MaintenanceRequest $request, User $actor, ?string $reason = null): MaintenanceRequest
    {
        $request->forceFill([
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'closure_notes' => $reason,
        ])->save();

        return $this->transition($request, MaintenanceRequestStatus::Cancelled, $actor, $reason);
    }

    public function transition(
        MaintenanceRequest $request,
        MaintenanceRequestStatus $target,
        User $actor,
        ?string $reason = null,
        ?OfficialNotificationType $notificationType = null,
        bool $visibleToTenant = false,
    ): MaintenanceRequest {
        $from = $this->statusForRequest($request);

        if ($from === $target) {
            return $request;
        }

        if ($from?->isTerminal()) {
            throw ValidationException::withMessages(['status' => 'Pedidos em estado final não podem mudar de estado.']);
        }

        $request->forceFill([
            'status' => $target,
            'updated_by' => $actor->id,
        ])->save();

        $request->statusHistories()->create([
            'from_status' => $from,
            'to_status' => $target,
            'reason' => $reason,
            'changed_by' => $actor->id,
            'changed_at' => now(),
        ]);

        $this->auditLogger->record(
            AuditEvents::UPDATE,
            $request,
            'maintenance_requests',
            'maintenance_status_changed',
            'Estado do pedido de manutenção alterado.',
            oldValues: ['status' => $from?->value],
            newValues: ['status' => $target->value],
        );

        $this->history->record(
            $this->housingUnitForRequest($request),
            TechnicalHistoryEventType::MaintenanceStatusChanged,
            'Estado de manutenção alterado para '.$target->label(),
            $reason,
            $actor,
            $request->leaseContract,
            $request,
            visibleToTenant: $visibleToTenant,
        );

        if ($notificationType) {
            $this->notifications->maintenanceStatus(
                $request->refresh(),
                $notificationType,
                'Atualização do pedido '.$request->request_number,
                'O pedido de manutenção está agora no estado '.$target->label().'.',
                $actor,
            );
        }

        return $request->refresh();
    }

    private function urgencyInput(MaintenanceRequest $request, bool|float|int|string|null $value): string
    {
        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        if ($value !== null) {
            throw ValidationException::withMessages([
                'urgency' => 'Urgência inválida.',
            ]);
        }

        $urgency = $request->getAttribute('urgency');

        return $urgency instanceof MaintenanceUrgency
            ? $urgency->value
            : (string) $urgency;
    }

    private function stringOrNull(bool|float|int|string|null $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * @param  array<int, MaintenanceRequestStatus>  $statuses
     */
    private function requestHasStatus(MaintenanceRequest $request, array $statuses): bool
    {
        $status = $this->statusForRequest($request);

        return $status instanceof MaintenanceRequestStatus
            && in_array($status, $statuses, true);
    }

    private function statusForRequest(MaintenanceRequest $request): ?MaintenanceRequestStatus
    {
        $status = $request->getAttribute('status');

        if ($status instanceof MaintenanceRequestStatus) {
            return $status;
        }

        return is_string($status) ? MaintenanceRequestStatus::tryFrom($status) : null;
    }

    private function housingUnitForRequest(MaintenanceRequest $request): HousingUnit
    {
        $housingUnit = $request->housingUnit;

        if (! $housingUnit instanceof HousingUnit) {
            throw ValidationException::withMessages([
                'housing_unit' => 'O pedido de manutenção não tem fogo associado.',
            ]);
        }

        return $housingUnit;
    }
}
