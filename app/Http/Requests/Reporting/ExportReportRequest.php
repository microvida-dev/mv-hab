<?php

namespace App\Http\Requests\Reporting;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Models\ReportDefinition;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Illuminate\Validation\Rule;

class ExportReportRequest extends DashboardFilterRequest
{
    public function authorize(): bool
    {
        /** @var ReportDefinition|null $reportDefinition */
        $reportDefinition = $this->route('reportDefinition');

        return $reportDefinition instanceof ReportDefinition
            && $reportDefinition->code !== TemporalApplicationResultExportService::REPORT_CODE
            && ($this->user()?->can('export', $reportDefinition) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ReportDefinition|null $reportDefinition */
        $reportDefinition = $this->route('reportDefinition');

        $confirmationRules =
            $reportDefinition !== null
            && $reportDefinition->sensitivity_level !== null
            && $reportDefinition->sensitivity_level->requiresConfirmation()
                ? ['required', 'accepted']
                : ['nullable'];
        $availableFormats = $reportDefinition?->getAttribute('available_formats');
        $availableFormats = is_array($availableFormats)
            ? $availableFormats
            : [];

        return array_merge(parent::rules(), [
            'format' => [
                'required',
                Rule::in(array_values(array_intersect(
                    ReportFormat::legacyValues(),
                    $availableFormats,
                ))),
            ],
            'scope' => ['required', Rule::enum(ExportScope::class)],
            'confirmed' => $confirmationRules,
        ]);
    }
}
