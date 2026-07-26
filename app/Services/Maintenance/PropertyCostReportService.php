<?php

namespace App\Services\Maintenance;

use App\Models\MaintenanceCost;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PropertyCostReportService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @return array<string, Collection<int, MaintenanceCost>>
     */
    public function summary(User $user): array
    {
        return [
            'by_property' => $this->costs($user)
                ->with('housingUnit')
                ->selectRaw(
                    'housing_unit_id, '
                    .'sum(amount) as total, '
                    .'count(*) as count',
                )
                ->groupBy('housing_unit_id')
                ->get(),

            'by_category' => $this->costs($user)
                ->join(
                    'maintenance_requests',
                    'maintenance_costs.maintenance_request_id',
                    '=',
                    'maintenance_requests.id',
                )
                ->leftJoin(
                    'maintenance_categories',
                    'maintenance_requests.maintenance_category_id',
                    '=',
                    'maintenance_categories.id',
                )
                ->selectRaw(
                    'coalesce('
                    .'maintenance_categories.name, '
                    .'"Sem categoria"'
                    .') as name, '
                    .'sum(maintenance_costs.amount) as total, '
                    .'count(*) as count',
                )
                ->groupBy('name')
                ->get(),

            'by_supplier' => $this->costs($user)
                ->with('supplier')
                ->selectRaw(
                    'maintenance_supplier_id, '
                    .'sum(amount) as total, '
                    .'count(*) as count',
                )
                ->groupBy('maintenance_supplier_id')
                ->get(),
        ];
    }

    /**
     * @return Builder<MaintenanceCost>
     */
    private function costs(User $user): Builder
    {
        return $this->municipalScope->maintenanceCosts(
            MaintenanceCost::query(),
            $user,
        );
    }
}
