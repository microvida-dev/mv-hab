<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionReport;
use App\Services\Inspections\PropertyInspectionReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PropertyInspectionController extends Controller
{
    public function __construct(private readonly PropertyInspectionReportService $reports) {}

    public function index(): View
    {
        Gate::authorize('viewAny', PropertyInspection::class);
        $inspections = PropertyInspection::query()
            ->where('tenant_visible', true)
            ->whereHas('leaseContract', fn ($query) => $query->where('user_id', $this->currentUser()->id))
            ->with(['housingUnit', 'report'])
            ->latest()
            ->paginate(15);

        return view('candidate.inspections.index', compact('inspections'));
    }

    public function show(PropertyInspection $propertyInspection): View
    {
        Gate::authorize('view', $propertyInspection);
        $propertyInspection->load(['housingUnit', 'items', 'attachments' => fn ($query) => $query->where('visible_to_tenant', true), 'report']);

        return view('candidate.inspections.show', compact('propertyInspection'));
    }

    public function downloadReport(PropertyInspectionReport $propertyInspectionReport): StreamedResponse
    {
        Gate::authorize('download', $propertyInspectionReport);

        return $this->reports->download($propertyInspectionReport, $this->currentUser());
    }
}
