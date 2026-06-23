<?php

namespace App\Http\Requests\Reporting;

class UpdateReportFilterPresetRequest extends StoreReportFilterPresetRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('reportFilterPreset')) ?? false;
    }
}
