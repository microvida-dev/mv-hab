<?php

namespace App\Services\Reporting\Indicators;

use App\Enums\InspectionStatus;
use App\Enums\MaintenanceCostStatus;
use App\Enums\MaintenanceRequestStatus;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Services\Reporting\ReportFilterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MaintenanceIndicatorsService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<MaintenanceRequest>
     */
    private function requests(array $filters): Builder
    {
        return $this->filters->applyThroughContract(MaintenanceRequest::query(), $filters, 'reported_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int|string, mixed>
     */
    public function countRequestsByStatus(array $filters): array
    {
        return $this->requests($filters)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status')->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countPendingRequests(array $filters): int
    {
        return $this->requests($filters)->whereIn('status', [MaintenanceRequestStatus::New->value, MaintenanceRequestStatus::UnderReview->value, MaintenanceRequestStatus::Open->value, MaintenanceRequestStatus::Scheduled->value, MaintenanceRequestStatus::InProgress->value])->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function averageResolutionTime(array $filters): float
    {
        $query = $this->requests($filters)->whereNotNull('reported_at')->whereNotNull('resolved_at');
        $expression = DB::connection()->getDriverName() === 'sqlite' ? 'AVG(julianday(resolved_at) - julianday(reported_at))' : 'AVG(TIMESTAMPDIFF(SECOND, reported_at, resolved_at)) / 86400';

        return round((float) $query->selectRaw("$expression as average_days")->value('average_days'), 2);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function totalMaintenanceCosts(array $filters): float
    {
        return (float) $this->filters->applyThroughContract(MaintenanceCost::query(), $filters, 'registered_at')->whereIn('status', [MaintenanceCostStatus::Approved->value, MaintenanceCostStatus::Incurred->value])->sum('amount');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int|string, mixed>
     */
    public function costsByProperty(array $filters): array
    {
        return $this->filters->applyThroughContract(MaintenanceCost::query(), $filters, 'registered_at')->select('housing_unit_id', DB::raw('SUM(amount) as total'))->groupBy('housing_unit_id')->orderByDesc('total')->pluck('total', 'housing_unit_id')->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int|string, mixed>
     */
    public function requestsByCategory(array $filters): array
    {
        return $this->requests($filters)->select('maintenance_category_id', DB::raw('COUNT(*) as total'))->groupBy('maintenance_category_id')->pluck('total', 'maintenance_category_id')->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int|string, mixed>
     */
    public function propertiesWithMostOccurrences(array $filters): array
    {
        return $this->requests($filters)->select('housing_unit_id', DB::raw('COUNT(*) as total'))->groupBy('housing_unit_id')->orderByDesc('total')->limit(10)->pluck('total', 'housing_unit_id')->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countScheduledInspections(array $filters): int
    {
        return $this->filters->applyThroughContract(PropertyInspection::query(), $filters, 'scheduled_for')->where('status', InspectionStatus::Scheduled->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countCompletedInspections(array $filters): int
    {
        return $this->filters->applyThroughContract(PropertyInspection::query(), $filters, 'completed_at')->whereIn('status', [InspectionStatus::Completed->value, InspectionStatus::Validated->value, InspectionStatus::Closed->value])->count();
    }
}
