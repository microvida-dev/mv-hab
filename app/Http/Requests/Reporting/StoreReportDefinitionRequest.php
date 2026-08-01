<?php

namespace App\Http\Requests\Reporting;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Enums\ReportSensitivityLevel;
use App\Enums\ReportType;
use App\Models\ReportDefinition;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'createBackoffice',
            ReportDefinition::class,
        ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $reportDefinition = $this->route('reportDefinition');
        $isExistingTemporalDefinition = $reportDefinition instanceof ReportDefinition
            && $reportDefinition->code === TemporalApplicationResultExportService::REPORT_CODE
            && $this->string('code')->toString() === TemporalApplicationResultExportService::REPORT_CODE;
        $availableFormats = $isExistingTemporalDefinition
            ? [ReportFormat::Zip->value]
            : ReportFormat::legacyValues();

        return [
            'code' => ['required', 'alpha_dash', 'max:150', Rule::unique('report_definitions', 'code')->ignore($this->route('reportDefinition'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'report_type' => ['required', Rule::enum(ReportType::class)],
            'sensitivity_level' => ['required', Rule::enum(ReportSensitivityLevel::class)],
            'required_permission' => ['nullable', 'string', 'max:150'],
            'query_service' => ['required', 'string', 'max:255'],
            'query_method' => ['required', 'string', 'max:150'],
            'available_formats' => ['required', 'array', 'min:1'],
            'available_formats.*' => [Rule::in($availableFormats)],
            'available_scopes' => ['required', 'array', 'min:1'],
            'available_scopes.*' => [Rule::enum(ExportScope::class)],
            'requires_filters' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
