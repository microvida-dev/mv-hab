<?php

namespace App\Services\Maintenance;

use App\Enums\MaintenanceCostStatus;
use App\Enums\MaintenanceCostType;
use App\Enums\TechnicalHistoryEventType;
use App\Models\HousingUnit;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Properties\PropertyTechnicalHistoryService;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class MaintenanceCostService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PropertyTechnicalHistoryService $history,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function store(MaintenanceRequest $request, User $actor, array $data): MaintenanceCost
    {
        $cost = MaintenanceCost::query()->create([
            'maintenance_request_id' => $request->id,
            'maintenance_intervention_id' => $data['maintenance_intervention_id'] ?? null,
            'housing_unit_id' => $request->housing_unit_id,
            'lease_contract_id' => $request->lease_contract_id,
            'maintenance_supplier_id' => $data['maintenance_supplier_id'] ?? $data['supplier_id'] ?? null,
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
        $cost->forceFill(['status' => MaintenanceCostStatus::Estimated])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $cost, 'maintenance_requests', 'maintenance_cost_registered', 'Custo de manutenção registado.');

        $this->history->record(
            $this->housingUnitForRequest($request),
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

    public function approve(MaintenanceCost $cost, User $actor): MaintenanceCost
    {
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
