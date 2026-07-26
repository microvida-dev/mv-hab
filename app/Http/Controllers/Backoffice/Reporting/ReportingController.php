<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Models\ReportDefinition;
use App\Services\Reporting\ReportPermissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ReportingController extends Controller
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
    ) {}

    public function __invoke(): View
    {
        Gate::authorize('viewAnyBackoffice', ReportDefinition::class);

        return view('backoffice.reports.index', [
            'reports' => $this->permissions
                ->visibleReports(
                    ReportDefinition::query(),
                    $this->currentUser(),
                )
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
