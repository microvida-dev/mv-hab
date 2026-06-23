<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceUrgency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewMaintenanceRequestRequest extends FormRequest
{
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
            'technical_priority' => ['nullable', Rule::enum(MaintenanceUrgency::class)],
            'maintenance_category_id' => ['nullable', 'integer', 'exists:maintenance_categories,id'],
            'urgency' => ['required', Rule::enum(MaintenanceUrgency::class)],
            'review_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
