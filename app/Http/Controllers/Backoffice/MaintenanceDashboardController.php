<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Services\Maintenance\MaintenanceIndicatorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class MaintenanceDashboardController extends Controller
{
    public function __construct(private readonly MaintenanceIndicatorService $indicators) {}

    public function __invoke(): View
    {
        Gate::authorize('viewAny', MaintenanceRequest::class);

        return view('backoffice.maintenance.dashboard', ['metrics' => $this->indicators->dashboard()]);
    }
}
