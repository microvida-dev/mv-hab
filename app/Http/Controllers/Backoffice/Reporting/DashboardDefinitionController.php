<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Enums\DashboardType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\StoreDashboardDefinitionRequest;
use App\Http\Requests\Reporting\UpdateDashboardDefinitionRequest;
use App\Models\DashboardDefinition;
use App\Models\IndicatorDefinition;
use App\Services\Audit\AuditLogger;
use App\Services\Reporting\ReportPermissionService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DashboardDefinitionController extends Controller
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', DashboardDefinition::class);

        return view('backoffice.reports.dashboards.index', [
            'dashboards' => $this->permissions
                ->visibleDashboards(
                    DashboardDefinition::query(),
                    $this->currentUser(),
                )
                ->withCount('widgets')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('createBackoffice', DashboardDefinition::class);

        return view('backoffice.reports.dashboards.create', ['types' => DashboardType::options()]);
    }

    public function store(StoreDashboardDefinitionRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', DashboardDefinition::class);
        $dashboard = new DashboardDefinition;
        $dashboard->forceFill($request->validated() + ['created_by' => $this->authenticatedUser($request)->getKey(), 'updated_by' => $this->authenticatedUser($request)->getKey()])->save();
        $this->audit->record(
            AuditEvents::CREATE,
            $dashboard,
            'reports',
            'dashboard_definition_created',
            'Definição de dashboard criada.',
        );

        return redirect()->route('backoffice.reports.dashboards.edit', $dashboard)->with('success', 'Dashboard criado.');
    }

    public function edit(DashboardDefinition $dashboardDefinition): View
    {
        Gate::authorize('updateBackoffice', $dashboardDefinition);

        return view('backoffice.reports.dashboards.edit', [
            'dashboard' => $dashboardDefinition->load('widgets.indicator'),
            'types' => DashboardType::options(),
            'indicators' => $this->permissions
                ->visibleByRequiredPermission(
                    IndicatorDefinition::query(),
                    $this->currentUser(),
                    'indicator_definitions.view',
                )
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateDashboardDefinitionRequest $request, DashboardDefinition $dashboardDefinition): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $dashboardDefinition);
        $dashboardDefinition->forceFill($request->validated() + ['updated_by' => $this->authenticatedUser($request)->getKey()])->save();
        $this->audit->record(
            AuditEvents::UPDATE,
            $dashboardDefinition,
            'reports',
            'dashboard_definition_updated',
            'Definição de dashboard atualizada.',
        );

        return back()->with('success', 'Dashboard atualizado.');
    }

    public function destroy(DashboardDefinition $dashboardDefinition): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $dashboardDefinition);
        $dashboardDefinition->delete();
        $this->audit->record(
            AuditEvents::DELETE,
            $dashboardDefinition,
            'reports',
            'dashboard_definition_deleted',
            'Definição de dashboard arquivada.',
        );

        return redirect()->route('backoffice.reports.dashboards.index')->with('success', 'Dashboard arquivado.');
    }
}
