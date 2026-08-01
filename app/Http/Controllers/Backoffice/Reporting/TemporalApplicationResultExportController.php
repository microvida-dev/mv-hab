<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Data\Reports\ApplicationResultExportPreviewData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationReviewBatchCycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\PreviewTemporalApplicationResultExportRequest;
use App\Http\Requests\Reporting\StoreTemporalApplicationResultExportRequest;
use App\Models\ApplicationReviewBatch;
use App\Models\Contest;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class TemporalApplicationResultExportController extends Controller
{
    public function __construct(
        private readonly TemporalApplicationResultExportService $exports,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ReportExport::class);
        $user = $this->currentUser();

        return view('backoffice.reports.temporal-exports.index', [
            'exports' => $this->municipalScope
                ->reportExports(ReportExport::query(), $user)
                ->where('export_profile', TemporalApplicationResultExportService::PROFILE)
                ->with(['run.definition', 'user:id,name', 'contest:id,code,title'])
                ->latest()
                ->paginate(30),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('createTemporal', ReportExport::class);

        return $this->form($this->currentUser());
    }

    public function preview(
        PreviewTemporalApplicationResultExportRequest $request,
    ): View {
        $values = $request->validated();
        $preview = $this->exports->preview(
            $this->authenticatedUser($request),
            $values,
        );

        return $this->form(
            $this->authenticatedUser($request),
            $values,
            $preview,
        );
    }

    public function store(
        StoreTemporalApplicationResultExportRequest $request,
    ): RedirectResponse {
        $export = $this->exports->request(
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return redirect()
            ->route('backoffice.reports.temporal-exports.show', $export)
            ->with('success', 'A exportação foi colocada em fila.');
    }

    public function show(ReportExport $reportExport): View
    {
        abort_unless($reportExport->isTemporalApplicationResultExport(), 404);
        Gate::authorize('view', $reportExport);

        return view('backoffice.reports.temporal-exports.show', [
            'export' => $reportExport->load([
                'run.definition',
                'user:id,name',
                'contest:id,code,title',
                'municipality:id,code,name',
            ]),
        ]);
    }

    /** @param array<string, mixed> $values */
    private function form(
        User $user,
        array $values = [],
        ?ApplicationResultExportPreviewData $preview = null,
    ): View {
        $municipalityId = (int) $user->municipality_id;
        $contests = $this->municipalScope
            ->contests(Contest::query(), $user)
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'program_id', 'code', 'title', 'status']);
        $batches = ApplicationReviewBatch::query()
            ->where('municipality_id', $municipalityId)
            ->with('contest:id,code,title')
            ->orderByDesc('sealed_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get([
                'id',
                'public_id',
                'municipality_id',
                'contest_id',
                'cycle',
                'sequence_number',
                'status',
                'sealed_at',
            ]);

        return view('backoffice.reports.temporal-exports.create', [
            'contests' => $contests,
            'batches' => $batches,
            'modes' => ApplicationResultExportMode::cases(),
            'formats' => ApplicationResultExportFormat::cases(),
            'datasets' => ApplicationResultExportDataset::cases(),
            'phases' => ApplicationReviewBatchCycle::cases(),
            'values' => $values,
            'preview' => $preview,
            'idempotencyToken' => (string) ($values['idempotency_token'] ?? Str::uuid()),
        ]);
    }
}
