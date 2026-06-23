<?php

namespace App\Services\TenantPortal;

use App\Enums\InspectionStatus;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\TenantCommunicationStatus;
use App\Enums\TenantInvoiceStatus;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\TenantCommunication;
use App\Models\TenantInvoice;
use App\Models\User;

class TenantDashboardService
{
    public function __construct(private readonly TenantPortalAccessService $access) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(User $tenant): array
    {
        $contractIds = $this->access->activeContracts($tenant)->pluck('id');

        return [
            'contracts' => $contractIds->count(),
            'open_invoices' => TenantInvoice::query()
                ->where('user_id', $tenant->id)
                ->whereIn('status', [
                    TenantInvoiceStatus::Issued->value,
                    TenantInvoiceStatus::Sent->value,
                    TenantInvoiceStatus::PartiallyPaid->value,
                    TenantInvoiceStatus::Overdue->value,
                    TenantInvoiceStatus::UnderReview->value,
                ])
                ->count(),
            'amount_outstanding' => TenantInvoice::query()
                ->where('user_id', $tenant->id)
                ->sum('amount_outstanding'),
            'maintenance_open' => MaintenanceRequest::query()
                ->where('user_id', $tenant->id)
                ->whereNotIn('status', [
                    MaintenanceRequestStatus::Closed->value,
                    MaintenanceRequestStatus::Cancelled->value,
                    MaintenanceRequestStatus::Rejected->value,
                ])
                ->count(),
            'scheduled_inspections' => PropertyInspection::query()
                ->whereIn('lease_contract_id', $contractIds)
                ->where('tenant_visible', true)
                ->whereIn('status', [
                    InspectionStatus::Scheduled->value,
                    InspectionStatus::InProgress->value,
                ])
                ->count(),
            'open_communications' => TenantCommunication::query()
                ->where('user_id', $tenant->id)
                ->whereIn('status', [
                    TenantCommunicationStatus::Open->value,
                    TenantCommunicationStatus::AwaitingTenant->value,
                    TenantCommunicationStatus::AwaitingMunicipality->value,
                ])
                ->count(),
        ];
    }
}
