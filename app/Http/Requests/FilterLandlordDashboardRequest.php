<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterLandlordDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager', 'auditor']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
