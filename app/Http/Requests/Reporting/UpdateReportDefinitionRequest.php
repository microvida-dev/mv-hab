<?php

namespace App\Http\Requests\Reporting;

class UpdateReportDefinitionRequest extends StoreReportDefinitionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'updateBackoffice',
            $this->route('reportDefinition'),
        ) === true;
    }
}
