<?php

namespace App\Http\Requests\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class DownloadReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('download', $this->route('reportExport')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
