<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveAllocationReportRequest;
use App\Http\Requests\GenerateAllocationReportRequest;
use App\Models\AllocationReport;
use App\Models\AllocationRun;
use App\Services\Allocation\AllocationReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AllocationReportController extends Controller
{
    public function __construct(private readonly AllocationReportService $reportService) {}

    public function index(): View
    {
        Gate::authorize('viewAny', AllocationReport::class);

        return view('backoffice.allocation.reports.index', [
            'reports' => AllocationReport::query()->with(['contest', 'allocationRun'])->latest()->paginate(15),
            'runs' => AllocationRun::query()->with('contest')->latest()->get(),
        ]);
    }

    public function store(GenerateAllocationReportRequest $request): RedirectResponse
    {
        Gate::authorize('create', AllocationReport::class);
        $run = AllocationRun::query()->findOrFail($request->integer('allocation_run_id'));
        $report = $this->reportService->generate($run, $this->authenticatedUser($request));

        return to_route('backoffice.allocation.reports.show', $report)->with('success', 'Relatório gerado.');
    }

    public function show(AllocationReport $allocationReport): View
    {
        Gate::authorize('view', $allocationReport);
        $allocationReport->load(['allocationRun', 'contest', 'generatedBy', 'approvedBy']);

        return view('backoffice.allocation.reports.show', compact('allocationReport'));
    }

    public function approve(ApproveAllocationReportRequest $request, AllocationReport $allocationReport): RedirectResponse
    {
        Gate::authorize('approve', $allocationReport);
        $this->reportService->approve($allocationReport, $this->authenticatedUser($request));

        return back()->with('success', 'Relatório aprovado.');
    }

    public function download(AllocationReport $allocationReport): StreamedResponse
    {
        Gate::authorize('view', $allocationReport);

        abort_unless($allocationReport->file_path && Storage::disk($allocationReport->file_disk ?? 'local')->exists($allocationReport->file_path), 404);

        return Storage::disk($allocationReport->file_disk ?? 'local')->download($allocationReport->file_path);
    }
}
