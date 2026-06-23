<?php

namespace App\Http\Requests\Reporting;

class UpdateDashboardWidgetRequest extends StoreDashboardWidgetRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('dashboardWidget')) ?? false;
    }
}
