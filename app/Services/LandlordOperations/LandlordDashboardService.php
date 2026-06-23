<?php

namespace App\Services\LandlordOperations;

use App\Enums\ContractStatus;
use App\Enums\InspectionStatus;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\TenantCommunicationStatus;
use App\Enums\TenantInvoiceStatus;
use App\Models\Contract;
use App\Models\LandlordDashboardSnapshot;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\TenantCommunication;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\User;

class LandlordDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function metrics(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return [
            'total_tenants' => Contract::query()->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'active_contracts' => Contract::query()->where('status', ContractStatus::Active->value)->count(),
            'active_invoices' => TenantInvoice::query()->whereIn('status', [
                TenantInvoiceStatus::Issued->value,
                TenantInvoiceStatus::Sent->value,
                TenantInvoiceStatus::PartiallyPaid->value,
                TenantInvoiceStatus::UnderReview->value,
            ])->count(),
            'overdue_invoices' => TenantInvoice::query()->where('status', TenantInvoiceStatus::Overdue->value)->count(),
            'open_maintenance_requests' => MaintenanceRequest::query()->whereNotIn('status', [
                MaintenanceRequestStatus::Closed->value,
                MaintenanceRequestStatus::Cancelled->value,
                MaintenanceRequestStatus::Rejected->value,
            ])->count(),
            'scheduled_inspections' => PropertyInspection::query()->whereIn('status', [
                InspectionStatus::Scheduled->value,
                InspectionStatus::InProgress->value,
            ])->count(),
            'unread_tenant_messages' => TenantCommunication::query()->whereIn('status', [
                TenantCommunicationStatus::Open->value,
                TenantCommunicationStatus::AwaitingMunicipality->value,
            ])->count(),
            'monthly_billed' => TenantInvoice::query()->whereBetween('issue_date', [$start, $end])->sum('amount_due'),
            'monthly_collected' => TenantPayment::query()->whereBetween('payment_date', [$start, $end])->whereIn('status', ['confirmed', 'reconciled'])->sum('amount'),
        ];
    }

    public function snapshot(?User $actor = null): LandlordDashboardSnapshot
    {
        $metrics = $this->metrics();

        return LandlordDashboardSnapshot::query()->updateOrCreate(
            ['snapshot_date' => now()->toDateString()],
            array_merge($metrics, [
                'status' => 'generated',
                'generated_at' => now(),
                'payload' => $metrics,
                'created_by' => $actor?->id,
            ]),
        );
    }
}
