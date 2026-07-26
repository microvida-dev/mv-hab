<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\StoreDashboardWidgetRequest;
use App\Http\Requests\Reporting\UpdateDashboardWidgetRequest;
use App\Models\DashboardWidget;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DashboardWidgetController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function store(StoreDashboardWidgetRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', DashboardWidget::class);
        $widget = new DashboardWidget;
        $widget->forceFill($request->validated())->save();
        $this->audit->record(
            AuditEvents::CREATE,
            $widget,
            'reports',
            'dashboard_widget_created',
            'Widget de dashboard criado.',
        );

        return back()->with('success', 'Widget adicionado.');
    }

    public function update(UpdateDashboardWidgetRequest $request, DashboardWidget $dashboardWidget): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $dashboardWidget);
        $dashboardWidget->forceFill($request->validated())->save();
        $this->audit->record(
            AuditEvents::UPDATE,
            $dashboardWidget,
            'reports',
            'dashboard_widget_updated',
            'Widget de dashboard atualizado.',
        );

        return back()->with('success', 'Widget atualizado.');
    }

    public function destroy(DashboardWidget $dashboardWidget): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $dashboardWidget);
        $dashboardWidget->delete();
        $this->audit->record(
            AuditEvents::DELETE,
            $dashboardWidget,
            'reports',
            'dashboard_widget_deleted',
            'Widget de dashboard removido.',
        );

        return back()->with('success', 'Widget removido.');
    }
}
