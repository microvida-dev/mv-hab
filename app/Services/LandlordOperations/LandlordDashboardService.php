<?php

namespace App\Services\LandlordOperations;

use App\Enums\ContractStatus;
use App\Enums\InspectionStatus;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\TenantCommunicationStatus;
use App\Enums\TenantInvoiceStatus;
use App\Models\Contract;
use App\Models\HousingUnit;
use App\Models\LandlordDashboardSnapshot;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\TenantCommunication;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use LogicException;

class LandlordDashboardService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function metrics(User $actor): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $contracts = $this->municipalScope->contracts(Contract::query(), $actor);
        $contractIds = (clone $contracts)->select('id');
        $housingUnitIds = $this->municipalScope
            ->housingUnits(HousingUnit::query(), $actor)
            ->select('id');

        return [
            'total_tenants' => (clone $contracts)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'active_contracts' => (clone $contracts)
                ->where('status', ContractStatus::Active->value)
                ->count(),
            'active_invoices' => TenantInvoice::query()
                ->whereIn('lease_contract_id', clone $contractIds)
                ->whereIn('status', [
                    TenantInvoiceStatus::Issued->value,
                    TenantInvoiceStatus::Sent->value,
                    TenantInvoiceStatus::PartiallyPaid->value,
                    TenantInvoiceStatus::UnderReview->value,
                ])
                ->count(),
            'overdue_invoices' => TenantInvoice::query()
                ->whereIn('lease_contract_id', clone $contractIds)
                ->where('status', TenantInvoiceStatus::Overdue->value)
                ->count(),
            'open_maintenance_requests' => MaintenanceRequest::query()
                ->where(function ($requests) use ($contractIds, $housingUnitIds): void {
                    $requests
                        ->whereIn('lease_contract_id', clone $contractIds)
                        ->orWhereIn('housing_unit_id', clone $housingUnitIds);
                })
                ->whereNotIn('status', [
                    MaintenanceRequestStatus::Closed->value,
                    MaintenanceRequestStatus::Cancelled->value,
                    MaintenanceRequestStatus::Rejected->value,
                ])
                ->count(),
            'scheduled_inspections' => PropertyInspection::query()
                ->where(function ($inspections) use ($contractIds, $housingUnitIds): void {
                    $inspections
                        ->whereIn('lease_contract_id', clone $contractIds)
                        ->orWhereIn('housing_unit_id', clone $housingUnitIds);
                })
                ->whereIn('status', [
                    InspectionStatus::Scheduled->value,
                    InspectionStatus::InProgress->value,
                ])
                ->count(),
            'unread_tenant_messages' => $this->municipalScope
                ->tenantCommunications(TenantCommunication::query(), $actor)
                ->whereIn('status', [
                    TenantCommunicationStatus::Open->value,
                    TenantCommunicationStatus::AwaitingMunicipality->value,
                ])
                ->count(),
            'monthly_billed' => TenantInvoice::query()
                ->whereIn('lease_contract_id', clone $contractIds)
                ->whereBetween('issue_date', [$start, $end])
                ->sum('amount_due'),
            'monthly_collected' => TenantPayment::query()
                ->whereIn('lease_contract_id', clone $contractIds)
                ->whereBetween('payment_date', [$start, $end])
                ->whereIn('status', ['confirmed', 'reconciled'])
                ->sum('amount'),
        ];
    }

    public function snapshot(?User $actor = null): LandlordDashboardSnapshot
    {
        if (! $actor instanceof User) {
            throw new LogicException('O snapshot do senhorio exige um utilizador autenticado.');
        }

        $metrics = $this->metrics($actor);
        $attributes = array_merge($metrics, [
            'snapshot_date' => now()->toDateString(),
            'status' => 'generated',
            'generated_at' => now(),
            'payload' => $metrics,
            'created_by' => $actor->id,
        ]);

        if ($actor->municipality_id !== null) {
            return (new LandlordDashboardSnapshot)->forceFill($attributes);
        }

        return LandlordDashboardSnapshot::query()->updateOrCreate(
            ['snapshot_date' => now()->toDateString()],
            $attributes,
        );
    }
}
