<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMaintenanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['administrator', 'municipal_technician', 'maintenance_manager', 'auditor']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'max:80'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
