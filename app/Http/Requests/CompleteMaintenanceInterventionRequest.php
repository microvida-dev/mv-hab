<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesMaintenanceBooleans;
use Illuminate\Foundation\Http\FormRequest;

class CompleteMaintenanceInterventionRequest extends FormRequest
{
    use NormalizesMaintenanceBooleans;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'work_description' => ['required', 'string', 'min:10', 'max:10000'],
            'materials_used' => ['nullable', 'string', 'max:5000'],
            'result_summary' => ['required', 'string', 'min:10', 'max:5000'],
            'next_steps' => ['nullable', 'string', 'max:5000'],
            'requires_follow_up' => ['sometimes', 'boolean'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
