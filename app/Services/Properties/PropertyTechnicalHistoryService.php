<?php

namespace App\Services\Properties;

use App\Enums\TechnicalHistoryEventType;
use App\Models\Contract;
use App\Models\HousingUnit;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\PropertyHistoryEvent;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionReport;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class PropertyTechnicalHistoryService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        HousingUnit $housingUnit,
        TechnicalHistoryEventType $type,
        string $title,
        ?string $description = null,
        ?User $actor = null,
        ?Contract $contract = null,
        ?MaintenanceRequest $maintenanceRequest = null,
        ?MaintenanceIntervention $intervention = null,
        ?MaintenanceCost $cost = null,
        ?PropertyInspection $inspection = null,
        ?PropertyInspectionReport $report = null,
        bool $visibleToTenant = false,
        array $metadata = [],
    ): PropertyHistoryEvent {
        $event = PropertyHistoryEvent::query()->create([
            'housing_unit_id' => $housingUnit->id,
            'lease_contract_id' => $contract?->id,
            'maintenance_request_id' => $maintenanceRequest?->id,
            'maintenance_intervention_id' => $intervention?->id,
            'maintenance_cost_id' => $cost?->id,
            'property_inspection_id' => $inspection?->id,
            'property_inspection_report_id' => $report?->id,
            'event_type' => $type,
            'title' => $title,
            'description' => $description,
            'occurred_at' => now(),
            'visible_to_tenant' => $visibleToTenant,
            'actor_id' => $actor?->id,
            'metadata' => $metadata ?: null,
        ]);

        $this->auditLogger->record(
            AuditEvents::CREATE,
            $event,
            'maintenance_requests',
            'property_history_event_create',
            'Evento de histórico técnico criado.',
            metadata: ['event_type' => $type->value],
        );

        return $event;
    }
}
