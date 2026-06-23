<?php

namespace App\Services\TenantMaintenance;

use App\Models\MaintenanceRequest;
use Illuminate\Support\Collection;

class MaintenanceReportService
{
    /**
     * @return Collection<int, MaintenanceRequest>
     */
    public function latestOpenRequests(): Collection
    {
        return MaintenanceRequest::query()
            ->with(['housingUnit', 'leaseContract.candidate', 'category'])
            ->whereNotIn('status', ['closed', 'cancelled', 'rejected'])
            ->latest()
            ->limit(50)
            ->get();
    }
}
