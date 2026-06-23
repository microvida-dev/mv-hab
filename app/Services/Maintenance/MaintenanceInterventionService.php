<?php

namespace App\Services\Maintenance;

use App\Enums\MaintenanceInterventionStatus;
use App\Enums\TechnicalHistoryEventType;
use App\Models\HousingUnit;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Properties\PropertyTechnicalHistoryService;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class MaintenanceInterventionService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PropertyTechnicalHistoryService $history,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function store(MaintenanceRequest $request, User $actor, array $data): MaintenanceIntervention
    {
        $intervention = $request->interventions()->create([
            'housing_unit_id' => $request->housing_unit_id,
            'lease_contract_id' => $request->lease_contract_id,
            'performed_by_user_id' => $data['performed_by_user_id'] ?? null,
            'maintenance_supplier_id' => $data['maintenance_supplier_id'] ?? null,
            'status' => ! empty($data['scheduled_for']) ? MaintenanceInterventionStatus::Scheduled : MaintenanceInterventionStatus::Planned,
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'work_description' => $data['work_description'] ?? null,
            'materials_used' => $data['materials_used'] ?? null,
            'created_by' => $actor->id,
        ]);

        $this->auditLogger->record(AuditEvents::CREATE, $intervention, 'maintenance_requests', 'maintenance_intervention_created', 'Intervenção de manutenção criada.');

        return $intervention;
    }

    public function start(MaintenanceIntervention $intervention, User $actor): MaintenanceIntervention
    {
        $intervention->forceFill(['status' => MaintenanceInterventionStatus::InProgress, 'started_at' => now()])->save();

        return $intervention->refresh();
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function complete(MaintenanceIntervention $intervention, User $actor, array $data): MaintenanceIntervention
    {
        $intervention->forceFill([
            'status' => MaintenanceInterventionStatus::Completed,
            'completed_at' => now(),
            'work_description' => $data['work_description'],
            'materials_used' => $data['materials_used'] ?? null,
            'result_summary' => $data['result_summary'],
            'next_steps' => $data['next_steps'] ?? null,
            'requires_follow_up' => (bool) ($data['requires_follow_up'] ?? false),
            'follow_up_date' => $data['follow_up_date'] ?? null,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $intervention, 'maintenance_requests', 'maintenance_intervention_completed', 'Intervenção de manutenção concluída.');

        $this->history->record(
            $this->housingUnitForIntervention($intervention),
            TechnicalHistoryEventType::MaintenanceInterventionCompleted,
            'Intervenção de manutenção concluída',
            $intervention->result_summary,
            $actor,
            $intervention->leaseContract,
            $intervention->maintenanceRequest,
            $intervention,
            visibleToTenant: true,
        );

        return $intervention->refresh();
    }

    private function housingUnitForIntervention(MaintenanceIntervention $intervention): HousingUnit
    {
        $housingUnit = $intervention->housingUnit;

        if (! $housingUnit instanceof HousingUnit) {
            throw ValidationException::withMessages([
                'housing_unit' => 'A intervenção não tem fogo associado.',
            ]);
        }

        return $housingUnit;
    }

    public function cancel(MaintenanceIntervention $intervention, User $actor): MaintenanceIntervention
    {
        $intervention->forceFill(['status' => MaintenanceInterventionStatus::Cancelled])->save();

        return $intervention->refresh();
    }
}
