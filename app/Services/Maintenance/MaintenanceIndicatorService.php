<?php

namespace App\Services\Maintenance;

use App\Enums\MaintenanceRequestStatus;
use App\Enums\MaintenanceUrgency;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceIndicatorService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @return array{
     *     by_status: array<int|string, int>,
     *     urgent_count: int,
     *     emergency_count: int,
     *     average_resolution_hours: float|int,
     *     total_cost: float,
     *     costs_by_category: array<int|string, float|int|string>,
     *     top_units: Collection<int, MaintenanceRequest>,
     *     inspections_scheduled: int,
     *     inspections_completed: int,
     *     open_total: int
     * }
     */
    public function dashboard(User $user): array
    {
        $resolvedDurations = $this->requests($user)
            ->whereNotNull('resolved_at')
            ->whereNotNull('reported_at')
            ->get([
                'reported_at',
                'resolved_at',
            ])
            ->map(
                function (
                    MaintenanceRequest $request,
                ): float {
                    if (
                        $request->reported_at === null
                        || $request->resolved_at === null
                    ) {
                        return 0.0;
                    }

                    return $request->reported_at
                        ->diffInHours(
                            $request->resolved_at,
                        );
                },
            );

        return [
            'by_status' => $this->requests($user)
                ->select(
                    'status',
                    DB::raw('count(*) as aggregate'),
                )
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->all(),

            'urgent_count' => $this->requests($user)
                ->where(
                    'urgency',
                    MaintenanceUrgency::Urgent->value,
                )
                ->count(),

            'emergency_count' => $this->requests($user)
                ->where(
                    'urgency',
                    MaintenanceUrgency::Emergency->value,
                )
                ->count(),

            'average_resolution_hours' => $resolvedDurations->avg() ?? 0,

            'total_cost' => (float) $this->costs($user)
                ->sum('amount'),

            'costs_by_category' => $this->costs($user)
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
                    .'sum(maintenance_costs.amount) as total',
                )
                ->groupBy('name')
                ->pluck('total', 'name')
                ->all(),

            'top_units' => $this->requests($user)
                ->select(
                    'housing_unit_id',
                    DB::raw('count(*) as aggregate'),
                )
                ->groupBy('housing_unit_id')
                ->orderByDesc('aggregate')
                ->limit(5)
                ->with('housingUnit')
                ->get(),

            'inspections_scheduled' => $this->inspections($user)
                ->where('status', 'scheduled')
                ->count(),

            'inspections_completed' => $this->inspections($user)
                ->whereIn('status', [
                    'completed',
                    'validated',
                    'closed',
                ])
                ->count(),

            'open_total' => $this->requests($user)
                ->whereNotIn('status', [
                    MaintenanceRequestStatus::Resolved->value,
                    MaintenanceRequestStatus::Rejected->value,
                    MaintenanceRequestStatus::Closed->value,
                    MaintenanceRequestStatus::Cancelled->value,
                ])
                ->count(),
        ];
    }

    /**
     * @return Builder<MaintenanceRequest>
     */
    private function requests(User $user): Builder
    {
        return $this->municipalScope->maintenanceRequests(
            MaintenanceRequest::query(),
            $user,
        );
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

    /**
     * @return Builder<PropertyInspection>
     */
    private function inspections(User $user): Builder
    {
        return $this->municipalScope->propertyInspections(
            PropertyInspection::query(),
            $user,
        );
    }
}
