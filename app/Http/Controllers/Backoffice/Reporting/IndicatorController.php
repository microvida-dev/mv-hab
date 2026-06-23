<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\DashboardFilterRequest;
use App\Http\Requests\Reporting\StoreIndicatorDefinitionRequest;
use App\Http\Requests\Reporting\UpdateIndicatorDefinitionRequest;
use App\Models\IndicatorDefinition;
use App\Services\Reporting\IndicatorCalculationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class IndicatorController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', IndicatorDefinition::class);
        $allowed = IndicatorDefinition::query()->get()->filter(fn ($indicator) => $this->currentUser()->can('view', $indicator))->pluck('id');

        return view('backoffice.reports.indicators.index', ['indicators' => IndicatorDefinition::query()->whereIn('id', $allowed)->orderBy('category')->orderBy('name')->paginate(30)]);
    }

    public function show(DashboardFilterRequest $request, IndicatorDefinition $indicatorDefinition, IndicatorCalculationService $calculator): View
    {
        Gate::authorize('view', $indicatorDefinition);

        return view('backoffice.reports.indicators.show', [
            'indicator' => $indicatorDefinition,
            'result' => $calculator->calculate($indicatorDefinition, $request->validated(), $this->authenticatedUser($request), true),
        ]);
    }

    public function store(StoreIndicatorDefinitionRequest $request): RedirectResponse
    {
        $indicator = new IndicatorDefinition;
        $indicator->forceFill($request->validated())->save();

        return redirect()->route('backoffice.reports.indicators.show', $indicator)->with('success', 'Indicador criado.');
    }

    public function update(UpdateIndicatorDefinitionRequest $request, IndicatorDefinition $indicatorDefinition): RedirectResponse
    {
        $indicatorDefinition->forceFill($request->validated())->save();

        return back()->with('success', 'Indicador atualizado.');
    }
}
