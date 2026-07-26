<?php

namespace App\Services\TenantMaintenance;

use App\Models\Contract;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Support\Collection;

class MaintenanceReportService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @return Collection<int, MaintenanceRequest>
     */
    public function latestOpenRequests(User $actor): Collection
    {
        $contractIds = $this->municipalScope
            ->contracts(Contract::query(), $actor)
            ->select('id');
        $housingUnitIds = $this->municipalScope
            ->housingUnits(HousingUnit::query(), $actor)
            ->select('id');

        return MaintenanceRequest::query()
            ->with(['housingUnit', 'leaseContract.candidate', 'category'])
            ->where(function ($requests) use ($contractIds, $housingUnitIds): void {
                $requests
                    ->whereIn('lease_contract_id', clone $contractIds)
                    ->orWhereIn('housing_unit_id', clone $housingUnitIds);
            })
            ->whereNotIn('status', ['closed', 'cancelled', 'rejected'])
            ->latest()
            ->limit(50)
            ->get();
    }
}
