<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\DownloadApplicationReportRequest;
use App\Http\Requests\GenerateApplicationReportRequest;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Services\OperationalReports\ApplicationReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationReportController extends Controller
{
    public function __construct(private readonly ApplicationReportService $reports) {}

    public function show(Application $application): View
    {
        Gate::authorize('viewAny', ApplicationReport::class);
        $reports = $application->applicationReports()->latest()->paginate(10);

        return view('backoffice.application-reports.show', compact('application', 'reports'));
    }

    public function generate(GenerateApplicationReportRequest $request, Application $application): RedirectResponse
    {
        Gate::authorize('create', ApplicationReport::class);
        $report = $this->reports->generate($application, $this->authenticatedUser($request), $request->validated());

        return to_route('backoffice.applications.report.show', $application)->with('success', 'Relatório gerado: '.$report->report_number);
    }

    public function download(DownloadApplicationReportRequest $request, ApplicationReport $applicationReport): StreamedResponse
    {
        Gate::authorize('download', $applicationReport);
        abort_if($applicationReport->file_path === null || ! Storage::disk('local')->exists($applicationReport->file_path), 404);

        return Storage::disk('local')->download($applicationReport->file_path, $applicationReport->report_number.'.html');
    }
}
