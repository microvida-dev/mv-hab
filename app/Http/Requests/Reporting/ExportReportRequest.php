<?php

namespace App\Http\Requests\Reporting;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Models\ReportDefinition;
use Illuminate\Validation\Rule;

class ExportReportRequest extends DashboardFilterRequest
{
    public function authorize(): bool
    {
        /** @var ReportDefinition|null $reportDefinition */
        $reportDefinition = $this->route('reportDefinition');

        return $this->user()?->can('export', $reportDefinition) ?? false;
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

        return array_merge(parent::rules(), [
            'format' => ['required', Rule::enum(ReportFormat::class)],
            'scope' => ['required', Rule::enum(ExportScope::class)],
            'confirmed' => $confirmationRules,
        ]);
    }
}
