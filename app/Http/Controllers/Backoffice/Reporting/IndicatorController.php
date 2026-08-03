<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\DashboardFilterRequest;
use App\Http\Requests\Reporting\StoreIndicatorDefinitionRequest;
use App\Http\Requests\Reporting\UpdateIndicatorDefinitionRequest;
use App\Models\IndicatorDefinition;
use App\Services\Audit\AuditLogger;
use App\Services\Reporting\IndicatorCalculationService;
use App\Services\Reporting\ReportPermissionService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class IndicatorController extends Controller
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', IndicatorDefinition::class);

        return view('backoffice.reports.indicators.index', [
            'indicators' => $this->permissions
                ->visibleByRequiredPermission(
                    IndicatorDefinition::query(),
                    $this->currentUser(),
                    'indicator_definitions.view',
                )
                ->orderBy('category')
                ->orderBy('name')
                ->paginate(30),
        ]);
    }

    public function show(DashboardFilterRequest $request, IndicatorDefinition $indicatorDefinition, IndicatorCalculationService $calculator): View
    {
        Gate::authorize('viewBackoffice', $indicatorDefinition);

        return view('backoffice.reports.indicators.show', [
            'indicator' => $indicatorDefinition,
            'result' => $calculator->calculate($indicatorDefinition, $request->validated(), $this->authenticatedUser($request), true),
        ]);
    }

    public function store(StoreIndicatorDefinitionRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', IndicatorDefinition::class);
        $indicator = new IndicatorDefinition;
        $indicator->forceFill($request->validated())->save();
        $this->audit->record(
            AuditEvents::CREATE,
            $indicator,
            'reports',
            'indicator_definition_created',
            'Definição de indicador criada.',
        );

        return redirect()->route('backoffice.reports.indicators.show', $indicator)->with('success', 'Indicador criado.');
    }

    public function update(UpdateIndicatorDefinitionRequest $request, IndicatorDefinition $indicatorDefinition): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $indicatorDefinition);
        $indicatorDefinition->forceFill($request->validated())->save();
        $this->audit->record(
            AuditEvents::UPDATE,
            $indicatorDefinition,
            'reports',
            'indicator_definition_updated',
            'Definição de indicador atualizada.',
        );

        return back()->with('success', 'Indicador atualizado.');
    }
}
