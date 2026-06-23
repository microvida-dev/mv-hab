<?php

namespace App\Http\Requests\Reporting;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use Illuminate\Validation\Rule;

class RunReportRequest extends DashboardFilterRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('run', $this->route('reportDefinition')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'format' => ['nullable', Rule::enum(ReportFormat::class)],
            'scope' => ['nullable', Rule::enum(ExportScope::class)],
        ];
    }
}
