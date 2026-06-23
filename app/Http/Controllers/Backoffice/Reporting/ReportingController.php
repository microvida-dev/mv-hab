<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Models\ReportDefinition;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ReportingController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('viewAny', ReportDefinition::class);

        return view('backoffice.reports.index', [
            'reports' => ReportDefinition::query()->where('is_active', true)->orderBy('name')->get()->filter(fn ($report) => $this->currentUser()->can('view', $report)),
        ]);
    }
}
