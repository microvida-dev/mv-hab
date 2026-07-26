<?php

namespace App\Http\Requests\Reporting;

use App\Models\ReportFilterPreset;
use Illuminate\Foundation\Http\FormRequest;

class StoreReportFilterPresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'createBackoffice',
            ReportFilterPreset::class,
        ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_definition_id' => ['required', 'integer', 'exists:report_definitions,id'],
            'name' => ['required', 'string', 'max:120'],
            'filters' => ['required', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
