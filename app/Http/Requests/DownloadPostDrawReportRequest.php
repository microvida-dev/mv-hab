<?php

namespace App\Http\Requests;

use App\Models\PostDrawReport;
use Illuminate\Foundation\Http\FormRequest;

class DownloadPostDrawReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('postDrawReport');

        return $report instanceof PostDrawReport
            && ($this->user()?->can('exportBackoffice', $report) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
