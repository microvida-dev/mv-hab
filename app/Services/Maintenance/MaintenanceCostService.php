<?php

namespace App\Services\Maintenance;

use App\Enums\MaintenanceCostStatus;
use App\Enums\MaintenanceCostType;
use App\Enums\TechnicalHistoryEventType;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\OperationalMunicipalContextService;
use App\Services\Properties\PropertyTechnicalHistoryService;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class MaintenanceCostService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PropertyTechnicalHistoryService $history,
        private readonly OperationalMunicipalContextService $municipalContext,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function store(MaintenanceRequest $request, User $actor, array $data): MaintenanceCost
    {
        $housingUnit = $this->municipalContext
            ->maintenanceRequestHousingUnit(
                $actor,
                $request,
            );

        $contract = $this->municipalContext
            ->contractForHousingUnit(
                $request->lease_contract_id,
                $housingUnit,
            );

        $intervention = $this->municipalContext
            ->interventionForRequest(
                $data['maintenance_intervention_id'] ?? null,
                $request,
            );

        $maintenanceSupplierId = $data[
            'maintenance_supplier_id'
        ] ?? null;

        $supplierAliasId = $data['supplier_id'] ?? null;

        if (
            $maintenanceSupplierId !== null
            && $supplierAliasId !== null
            && (string) $maintenanceSupplierId
                !== (string) $supplierAliasId
        ) {
            throw ValidationException::withMessages([
                'maintenance_supplier_id' => 'Os identificadores de fornecedor não coincidem.',
            ]);
        }

        $supplier = $this->municipalContext
            ->supplierForHousingUnit(
                $maintenanceSupplierId ?? $supplierAliasId,
                $housingUnit,
            );

        $cost = MaintenanceCost::query()->create([
            'maintenance_request_id' => $request->id,
            'maintenance_intervention_id' => $intervention?->id,
            'housing_unit_id' => $housingUnit->id,
            'lease_contract_id' => $contract?->id,
            'maintenance_supplier_id' => $supplier?->id,
            'cost_type' => $this->costTypeFromData($data),
            'description' => $data['description'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'EUR',
            'invoice_reference' => $data['invoice_reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'registered_by' => $actor->id,
            'registered_at' => now(),
        ]);

        $cost->forceFill([
            'status' => MaintenanceCostStatus::Estimated,
        ])->save();

        $this->auditLogger->record(
            AuditEvents::CREATE,
            $cost,
            'maintenance_requests',
            'maintenance_cost_registered',
            'Custo de manutenção registado.',
        );

        $this->history->record(
            $housingUnit,
            TechnicalHistoryEventType::MaintenanceCostRegistered,
            'Custo de manutenção registado',
            $cost->description,
            $actor,
            $request->leaseContract,
            $request,
            cost: $cost,
        );

        return $cost->refresh();
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    private function costTypeFromData(array $data): MaintenanceCostType
    {
        $value = $data['cost_type'] ?? null;

        if (! is_int($value) && ! is_string($value)) {
            throw ValidationException::withMessages([
                'cost_type' => 'Tipo de custo inválido.',
            ]);
        }

        return MaintenanceCostType::from($value);
    }

    public function approve(MaintenanceCost $cost, User $actor): MaintenanceCost
    {
        $this->municipalContext
            ->maintenanceCostHousingUnit(
                $actor,
                $cost,
            );

        $cost->forceFill([
            'status' => MaintenanceCostStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $cost, 'maintenance_requests', 'maintenance_cost_approved', 'Custo de manutenção aprovado.');

        return $cost->refresh();
    }

    public function reject(MaintenanceCost $cost, User $actor, string $reason): MaintenanceCost
    {
        $this->municipalContext
            ->maintenanceCostHousingUnit(
                $actor,
                $cost,
            );

        $cost->forceFill([
            'status' => MaintenanceCostStatus::Rejected,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ])->save();

        $this->auditLogger->record(AuditEvents::REJECT, $cost, 'maintenance_requests', 'maintenance_cost_rejected', 'Custo de manutenção rejeitado.');

        return $cost->refresh();
    }
}
