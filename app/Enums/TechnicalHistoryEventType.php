<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TechnicalHistoryEventType: string
{
    use HasOptions;

    case MaintenanceRequestCreated = 'maintenance_request_created';
    case MaintenanceStatusChanged = 'maintenance_status_changed';
    case MaintenanceAssigned = 'maintenance_assigned';
    case MaintenanceInterventionCompleted = 'maintenance_intervention_completed';
    case MaintenanceCostRegistered = 'maintenance_cost_registered';
    case InspectionCreated = 'inspection_created';
    case InspectionCompleted = 'inspection_completed';
    case InspectionValidated = 'inspection_validated';
    case InspectionReportIssued = 'inspection_report_issued';
    case ContractStarted = 'contract_started';
    case ContractEnded = 'contract_ended';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MaintenanceRequestCreated => 'Pedido de manutenção criado',
            self::MaintenanceStatusChanged => 'Estado de manutenção alterado',
            self::MaintenanceAssigned => 'Pedido de manutenção atribuído',
            self::MaintenanceInterventionCompleted => 'Intervenção concluída',
            self::MaintenanceCostRegistered => 'Custo de manutenção registado',
            self::InspectionCreated => 'Vistoria criada',
            self::InspectionCompleted => 'Vistoria concluída',
            self::InspectionValidated => 'Vistoria validada',
            self::InspectionReportIssued => 'Auto de vistoria emitido',
            self::ContractStarted => 'Contrato iniciado',
            self::ContractEnded => 'Contrato terminado',
            self::Other => 'Outro',
        };
    }
}
