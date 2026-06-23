<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\RunReportRequest;
use App\Models\ReportDefinition;
use App\Models\ReportRun;
use App\Services\Reporting\ReportRunService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReportRunController extends Controller
{
    public function __construct(
        private readonly ReportRunService $runs,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ReportRun::class);

        $allowedReportIds = ReportDefinition::query()
            ->get()
            ->filter(
                fn (ReportDefinition $report): bool => $this->currentUser()->can('view', $report)
            )
            ->pluck('id');

        return view('backoffice.reports.runs.index', [
            'runs' => ReportRun::query()
                ->whereIn('report_definition_id', $allowedReportIds)
                ->with([
                    'definition',
                    'user',
                ])
                ->latest()
                ->paginate(30),
        ]);
    }

    public function store(
        RunReportRequest $request,
        ReportDefinition $reportDefinition,
    ): RedirectResponse {
        $result = $this->runs->run(
            $reportDefinition,
            $this->authenticatedUser($request),
            $request->safe()->except(['format', 'scope']),
            ReportFormat::tryFrom($request->string('format')->toString()) ?? ReportFormat::Html,
            ExportScope::tryFrom($request->string('scope')->toString()) ?? ExportScope::Aggregated,
        );

        return redirect()->route(
            'backoffice.reports.runs.show',
            $result->reportRun,
        );
    }

    public function show(ReportRun $reportRun): View
    {
        Gate::authorize('view', $reportRun);

        return view('backoffice.reports.runs.show', [
            'run' => $reportRun->load([
                'definition',
                'user',
                'exports',
            ]),
            'rows' => $this->runs->replay(
                $reportRun,
                $this->currentUser(),
            ),
        ]);
    }
}
