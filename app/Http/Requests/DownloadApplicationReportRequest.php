<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DownloadApplicationReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reports.export') || $this->user()?->hasPermission('reports.view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
