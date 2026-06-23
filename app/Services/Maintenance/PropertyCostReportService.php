<?php

namespace App\Services\Maintenance;

use App\Models\MaintenanceCost;
use Illuminate\Database\Eloquent\Collection;

class PropertyCostReportService
{
    /**
     * @return array<string, Collection<int, MaintenanceCost>>
     */
    public function summary(): array
    {
        return [
            'by_property' => MaintenanceCost::query()
                ->with('housingUnit')
                ->selectRaw('housing_unit_id, sum(amount) as total, count(*) as count')
                ->groupBy('housing_unit_id')
                ->get(),
            'by_category' => MaintenanceCost::query()
                ->join('maintenance_requests', 'maintenance_costs.maintenance_request_id', '=', 'maintenance_requests.id')
                ->leftJoin('maintenance_categories', 'maintenance_requests.maintenance_category_id', '=', 'maintenance_categories.id')
                ->selectRaw('coalesce(maintenance_categories.name, "Sem categoria") as name, sum(maintenance_costs.amount) as total, count(*) as count')
                ->groupBy('name')
                ->get(),
            'by_supplier' => MaintenanceCost::query()
                ->with('supplier')
                ->selectRaw('maintenance_supplier_id, sum(amount) as total, count(*) as count')
                ->groupBy('maintenance_supplier_id')
                ->get(),
        ];
    }
}
