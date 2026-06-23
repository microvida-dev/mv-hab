<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateMaintenanceReportRequest;
use App\Services\TenantMaintenance\MaintenanceReportService;
use Illuminate\Contracts\View\View;

class TenantMaintenanceReportController extends Controller
{
    public function __construct(private readonly MaintenanceReportService $reports) {}

    public function index(GenerateMaintenanceReportRequest $request): View
    {
        return view('backoffice.maintenance-reports.index', [
            'requests' => $this->reports->latestOpenRequests(),
        ]);
    }
}
