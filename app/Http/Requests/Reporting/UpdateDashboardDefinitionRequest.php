<?php

namespace App\Http\Requests\Reporting;

class UpdateDashboardDefinitionRequest extends StoreDashboardDefinitionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('dashboardDefinition')) ?? false;
    }
}
