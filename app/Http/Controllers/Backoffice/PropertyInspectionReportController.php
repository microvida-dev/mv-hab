<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelPropertyInspectionReportRequest;
use App\Http\Requests\GeneratePropertyInspectionReportRequest;
use App\Http\Requests\ValidatePropertyInspectionReportRequest;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionReport;
use App\Services\Inspections\PropertyInspectionReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PropertyInspectionReportController extends Controller
{
    public function __construct(private readonly PropertyInspectionReportService $reports) {}

    public function generate(GeneratePropertyInspectionReportRequest $request, PropertyInspection $propertyInspection): RedirectResponse
    {
        Gate::authorize('update', $propertyInspection);
        $report = $this->reports->generate($propertyInspection, $this->authenticatedUser($request));

        return to_route('backoffice.inspections.reports.show', $report)->with('success', 'Auto gerado.');
    }

    public function show(PropertyInspectionReport $propertyInspectionReport): View
    {
        Gate::authorize('view', $propertyInspectionReport);
        $propertyInspectionReport->load('inspection.housingUnit', 'inspection.items');

        return view('backoffice.inspections.reports.show', compact('propertyInspectionReport'));
    }

    public function download(PropertyInspectionReport $propertyInspectionReport): StreamedResponse
    {
        Gate::authorize('download', $propertyInspectionReport);

        return $this->reports->download($propertyInspectionReport, $this->currentUser());
    }

    public function validateReport(ValidatePropertyInspectionReportRequest $request, PropertyInspectionReport $propertyInspectionReport): RedirectResponse
    {
        Gate::authorize('approve', $propertyInspectionReport);
        $this->reports->validate($propertyInspectionReport, $this->authenticatedUser($request));

        return back()->with('success', 'Auto validado.');
    }

    public function cancel(CancelPropertyInspectionReportRequest $request, PropertyInspectionReport $propertyInspectionReport): RedirectResponse
    {
        Gate::authorize('approve', $propertyInspectionReport);
        $this->reports->cancel($propertyInspectionReport, $this->authenticatedUser($request), $request->validated('cancellation_reason'));

        return back()->with('success', 'Auto cancelado.');
    }
}
