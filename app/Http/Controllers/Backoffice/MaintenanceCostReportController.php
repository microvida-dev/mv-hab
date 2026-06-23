<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceCost;
use App\Services\Maintenance\PropertyCostReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class MaintenanceCostReportController extends Controller
{
    public function __construct(private readonly PropertyCostReportService $reports) {}

    public function index(): View
    {
        Gate::authorize('viewAny', MaintenanceCost::class);

        return view('backoffice.maintenance.cost-reports.index', ['summary' => $this->reports->summary()]);
    }
}
