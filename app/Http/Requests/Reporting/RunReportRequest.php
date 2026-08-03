<?php

namespace App\Http\Requests\Reporting;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Models\ReportDefinition;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Illuminate\Validation\Rule;

class RunReportRequest extends DashboardFilterRequest
{
    public function authorize(): bool
    {
        $reportDefinition = $this->route('reportDefinition');

        return $reportDefinition instanceof ReportDefinition
            && $reportDefinition->code !== TemporalApplicationResultExportService::REPORT_CODE
            && $this->user()?->can(
                'runBackoffice',
                $reportDefinition,
            ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'format' => ['nullable', Rule::in(ReportFormat::legacyValues())],
            'scope' => ['nullable', Rule::enum(ExportScope::class)],
        ];
    }
}
