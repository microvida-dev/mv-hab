<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\ExportReportRequest;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Reporting\ReportExportService;
use App\Services\Reporting\ReportPermissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReportExportController extends Controller
{
    public function __construct(
        private readonly ReportExportService $exports,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly ReportPermissionService $permissions,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ReportExport::class);
        $allowed = ReportDefinition::query()
            ->get()
            ->filter(function (ReportDefinition $report): bool {
                if (! $this->currentUser()->can('view', $report)) {
                    return false;
                }

                return ! $this->permissions->isApplicationReport($report)
                    || $this->currentUser()->can('export', $report);
            })
            ->pluck('id');

        return view('backoffice.reports.exports.index', [
            'exports' => $this->municipalScope
                ->reportExports(ReportExport::query(), $this->currentUser())
                ->whereHas('run', fn ($query) => $query->whereIn('report_definition_id', $allowed))
                ->with(['run.definition', 'user'])
                ->latest()
                ->paginate(30),
        ]);
    }

    public function store(ExportReportRequest $request, ReportDefinition $reportDefinition): RedirectResponse
    {
        $export = $this->exports->export(
            $reportDefinition,
            $this->authenticatedUser($request),
            $request->safe()->except(['format', 'scope', 'confirmed']),
            ReportFormat::from($request->string('format')->toString()),
            ExportScope::from($request->string('scope')->toString()),
            $request->boolean('confirmed'),
        );

        $message = $export->requested_format !== $export->format
            ? 'Exportação concluída com fallback para '.$export->format->label().'.'
            : 'Exportação concluída.';

        return redirect()->route('backoffice.reports.exports.show', $export)->with('success', $message);
    }

    public function show(ReportExport $reportExport): View
    {
        Gate::authorize('view', $reportExport);

        return view('backoffice.reports.exports.show', ['export' => $reportExport->load(['run.definition', 'user', 'downloads'])]);
    }
}
