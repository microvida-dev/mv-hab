<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\StoreReportFilterPresetRequest;
use App\Http\Requests\Reporting\UpdateReportFilterPresetRequest;
use App\Models\ReportDefinition;
use App\Models\ReportFilterPreset;
use App\Services\Audit\AuditLogger;
use App\Services\Reporting\ReportPermissionService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReportFilterPresetController extends Controller
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', ReportFilterPreset::class);
        $reports = $this->permissions
            ->visibleReports(
                ReportDefinition::query(),
                $this->currentUser(),
            )
            ->where('is_active', true);

        return view('backoffice.reports.filter-presets.index', [
            'presets' => ReportFilterPreset::query()
                ->with('definition')
                ->where('user_id', $this->currentUser()->getKey())
                ->whereIn(
                    'report_definition_id',
                    (clone $reports)->select('id'),
                )
                ->orderBy('name')
                ->get(),
            'reports' => $reports->orderBy('name')->get(),
        ]);
    }

    public function store(StoreReportFilterPresetRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', ReportFilterPreset::class);
        abort_unless(
            $this->permissions
                ->visibleReports(
                    ReportDefinition::query(),
                    $this->authenticatedUser($request),
                )
                ->whereKey($request->integer('report_definition_id'))
                ->exists(),
            403,
        );
        $preset = new ReportFilterPreset($request->safe()->except('report_definition_id'));
        $preset->report_definition_id = $request->integer('report_definition_id');
        $preset->user_id = $this->authenticatedUser($request)->getKey();
        $preset->save();
        $this->audit->record(
            AuditEvents::CREATE,
            $preset,
            'reports',
            'report_filter_preset_created',
            'Filtro de relatório guardado.',
        );

        return back()->with('success', 'Filtro guardado.');
    }

    public function update(UpdateReportFilterPresetRequest $request, ReportFilterPreset $reportFilterPreset): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $reportFilterPreset);
        $reportFilterPreset->update($request->safe()->except('report_definition_id'));
        $this->audit->record(
            AuditEvents::UPDATE,
            $reportFilterPreset,
            'reports',
            'report_filter_preset_updated',
            'Filtro de relatório atualizado.',
        );

        return back()->with('success', 'Filtro atualizado.');
    }

    public function destroy(ReportFilterPreset $reportFilterPreset): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $reportFilterPreset);
        $reportFilterPreset->delete();
        $this->audit->record(
            AuditEvents::DELETE,
            $reportFilterPreset,
            'reports',
            'report_filter_preset_deleted',
            'Filtro de relatório removido.',
        );

        return back()->with('success', 'Filtro removido.');
    }
}
