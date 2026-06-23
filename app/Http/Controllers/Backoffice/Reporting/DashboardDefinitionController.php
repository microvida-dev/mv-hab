<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Enums\DashboardType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\StoreDashboardDefinitionRequest;
use App\Http\Requests\Reporting\UpdateDashboardDefinitionRequest;
use App\Models\DashboardDefinition;
use App\Models\IndicatorDefinition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DashboardDefinitionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DashboardDefinition::class);

        return view('backoffice.reports.dashboards.index', ['dashboards' => DashboardDefinition::query()->withCount('widgets')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        Gate::authorize('create', DashboardDefinition::class);

        return view('backoffice.reports.dashboards.create', ['types' => DashboardType::options()]);
    }

    public function store(StoreDashboardDefinitionRequest $request): RedirectResponse
    {
        $dashboard = new DashboardDefinition;
        $dashboard->forceFill($request->validated() + ['created_by' => $this->authenticatedUser($request)->getKey(), 'updated_by' => $this->authenticatedUser($request)->getKey()])->save();

        return redirect()->route('backoffice.reports.dashboards.edit', $dashboard)->with('success', 'Dashboard criado.');
    }

    public function edit(DashboardDefinition $dashboardDefinition): View
    {
        Gate::authorize('update', $dashboardDefinition);

        return view('backoffice.reports.dashboards.edit', ['dashboard' => $dashboardDefinition->load('widgets.indicator'), 'types' => DashboardType::options(), 'indicators' => IndicatorDefinition::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(UpdateDashboardDefinitionRequest $request, DashboardDefinition $dashboardDefinition): RedirectResponse
    {
        $dashboardDefinition->forceFill($request->validated() + ['updated_by' => $this->authenticatedUser($request)->getKey()])->save();

        return back()->with('success', 'Dashboard atualizado.');
    }

    public function destroy(DashboardDefinition $dashboardDefinition): RedirectResponse
    {
        Gate::authorize('delete', $dashboardDefinition);
        $dashboardDefinition->delete();

        return redirect()->route('backoffice.reports.dashboards.index')->with('success', 'Dashboard arquivado.');
    }
}
