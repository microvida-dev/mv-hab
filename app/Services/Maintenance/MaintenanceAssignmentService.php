<?php

namespace App\Services\Maintenance;

use App\Enums\MaintenanceAssignmentStatus;
use App\Enums\MaintenanceAssignmentType;
use App\Enums\TechnicalHistoryEventType;
use App\Models\HousingUnit;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Properties\PropertyTechnicalHistoryService;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class MaintenanceAssignmentService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PropertyTechnicalHistoryService $history,
    ) {}

    /**
     * @param  array<string, bool|int|string|null>  $data
     */
    public function assign(MaintenanceRequest $request, User $actor, array $data): MaintenanceAssignment
    {
        $type = $this->assignmentTypeFromData($data);

        if ($type === MaintenanceAssignmentType::InternalTechnician && empty($data['assigned_user_id'])) {
            throw ValidationException::withMessages(['assigned_user_id' => 'Indique o técnico interno.']);
        }

        if ($type === MaintenanceAssignmentType::ExternalSupplier && empty($data['maintenance_supplier_id'])) {
            throw ValidationException::withMessages(['maintenance_supplier_id' => 'Indique o fornecedor externo.']);
        }

        $request->assignments()
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->update(['status' => MaintenanceAssignmentStatus::Cancelled, 'cancelled_at' => now()]);

        $assignment = $request->assignments()->create([
            'assignment_type' => $type,
            'status' => MaintenanceAssignmentStatus::Assigned,
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
            'maintenance_supplier_id' => $data['maintenance_supplier_id'] ?? null,
            'assigned_by' => $actor->id,
            'assigned_at' => now(),
            'assignment_notes' => $data['assignment_notes'] ?? null,
        ]);

        $this->auditLogger->record(AuditEvents::UPDATE, $request, 'maintenance_requests', 'maintenance_assigned', 'Pedido de manutenção atribuído.');

        $this->history->record(
            $this->housingUnitForRequest($request),
            TechnicalHistoryEventType::MaintenanceAssigned,
            'Pedido de manutenção atribuído',
            $assignment->assignment_notes,
            $actor,
            $request->leaseContract,
            $request,
        );

        return $assignment;
    }

    public function cancel(MaintenanceAssignment $assignment, User $actor, ?string $reason = null): MaintenanceAssignment
    {
        $assignment->forceFill([
            'status' => MaintenanceAssignmentStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $assignment, 'maintenance_requests', 'maintenance_assignment_cancelled', 'Atribuição de manutenção cancelada.');

        return $assignment->refresh();
    }

    /**
     * @param  array<string, bool|int|string|null>  $data
     */
    private function assignmentTypeFromData(array $data): MaintenanceAssignmentType
    {
        $value = $data['assignment_type'] ?? null;

        if (! is_int($value) && ! is_string($value)) {
            throw ValidationException::withMessages([
                'assignment_type' => 'Tipo de atribuição inválido.',
            ]);
        }

        return MaintenanceAssignmentType::from($value);
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
