<?php

namespace App\Services\Maintenance;

use App\Enums\MaintenanceRequestStatus;
use App\Enums\MaintenanceUrgency;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceIndicatorService
{
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
    public function dashboard(): array
    {
        $resolvedDurations = MaintenanceRequest::query()
            ->whereNotNull('resolved_at')
            ->whereNotNull('reported_at')
            ->get(['reported_at', 'resolved_at'])
            ->map(function (MaintenanceRequest $request): float {
                if ($request->reported_at === null || $request->resolved_at === null) {
                    return 0.0;
                }

                return $request->reported_at->diffInHours($request->resolved_at);
            });

        return [
            'by_status' => MaintenanceRequest::query()
                ->select('status', DB::raw('count(*) as aggregate'))
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->all(),
            'urgent_count' => MaintenanceRequest::query()->where('urgency', MaintenanceUrgency::Urgent->value)->count(),
            'emergency_count' => MaintenanceRequest::query()->where('urgency', MaintenanceUrgency::Emergency->value)->count(),
            'average_resolution_hours' => $resolvedDurations->avg() ?? 0,
            'total_cost' => (float) MaintenanceCost::query()->sum('amount'),
            'costs_by_category' => MaintenanceCost::query()
                ->join('maintenance_requests', 'maintenance_costs.maintenance_request_id', '=', 'maintenance_requests.id')
                ->leftJoin('maintenance_categories', 'maintenance_requests.maintenance_category_id', '=', 'maintenance_categories.id')
                ->selectRaw('coalesce(maintenance_categories.name, "Sem categoria") as name, sum(maintenance_costs.amount) as total')
                ->groupBy('name')
                ->pluck('total', 'name')
                ->all(),
            'top_units' => MaintenanceRequest::query()
                ->select('housing_unit_id', DB::raw('count(*) as aggregate'))
                ->groupBy('housing_unit_id')
                ->orderByDesc('aggregate')
                ->limit(5)
                ->with('housingUnit')
                ->get(),
            'inspections_scheduled' => PropertyInspection::query()->where('status', 'scheduled')->count(),
            'inspections_completed' => PropertyInspection::query()->whereIn('status', ['completed', 'validated', 'closed'])->count(),
            'open_total' => MaintenanceRequest::query()->whereNotIn('status', [
                MaintenanceRequestStatus::Resolved,
                MaintenanceRequestStatus::Rejected,
                MaintenanceRequestStatus::Closed,
                MaintenanceRequestStatus::Cancelled,
            ])->count(),
        ];
    }
}
