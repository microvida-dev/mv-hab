<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterExecutiveDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reports.view') || $this->user()?->hasPermission('reports.view_executive');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['nullable', 'exists:programs,id'],
            'contest_id' => ['nullable', 'exists:contests,id'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
        ];
    }
}
