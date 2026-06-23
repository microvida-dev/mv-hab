<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\StoreDashboardWidgetRequest;
use App\Http\Requests\Reporting\UpdateDashboardWidgetRequest;
use App\Models\DashboardWidget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DashboardWidgetController extends Controller
{
    public function store(StoreDashboardWidgetRequest $request): RedirectResponse
    {
        $widget = new DashboardWidget;
        $widget->forceFill($request->validated())->save();

        return back()->with('success', 'Widget adicionado.');
    }

    public function update(UpdateDashboardWidgetRequest $request, DashboardWidget $dashboardWidget): RedirectResponse
    {
        $dashboardWidget->forceFill($request->validated())->save();

        return back()->with('success', 'Widget atualizado.');
    }

    public function destroy(DashboardWidget $dashboardWidget): RedirectResponse
    {
        Gate::authorize('delete', $dashboardWidget);
        $dashboardWidget->delete();

        return back()->with('success', 'Widget removido.');
    }
}
