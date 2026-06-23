<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\StoreReportFilterPresetRequest;
use App\Http\Requests\Reporting\UpdateReportFilterPresetRequest;
use App\Models\ReportDefinition;
use App\Models\ReportFilterPreset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReportFilterPresetController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ReportFilterPreset::class);
        $allowed = ReportDefinition::query()->where('is_active', true)->get()->filter(fn ($report) => $this->currentUser()->can('view', $report))->pluck('id');

        return view('backoffice.reports.filter-presets.index', [
            'presets' => ReportFilterPreset::query()->with('definition')->where('user_id', $this->currentUser()->getKey())->whereIn('report_definition_id', $allowed)->orderBy('name')->get(),
            'reports' => ReportDefinition::query()->whereIn('id', $allowed)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreReportFilterPresetRequest $request): RedirectResponse
    {
        $preset = new ReportFilterPreset($request->safe()->except('report_definition_id'));
        $preset->report_definition_id = $request->integer('report_definition_id');
        $preset->user_id = $this->authenticatedUser($request)->getKey();
        $preset->save();

        return back()->with('success', 'Filtro guardado.');
    }

    public function update(UpdateReportFilterPresetRequest $request, ReportFilterPreset $reportFilterPreset): RedirectResponse
    {
        $reportFilterPreset->update($request->safe()->except('report_definition_id'));

        return back()->with('success', 'Filtro atualizado.');
    }

    public function destroy(ReportFilterPreset $reportFilterPreset): RedirectResponse
    {
        Gate::authorize('delete', $reportFilterPreset);
        $reportFilterPreset->delete();

        return back()->with('success', 'Filtro removido.');
    }
}
