<?php

namespace App\Http\Requests;

use App\Enums\InspectionCondition;
use App\Http\Requests\Concerns\NormalizesMaintenanceBooleans;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompletePropertyInspectionRequest extends FormRequest
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
            'general_condition' => ['required', Rule::enum(InspectionCondition::class)],
            'summary' => ['required', 'string', 'min:10', 'max:10000'],
            'recommendations' => ['nullable', 'string', 'max:10000'],
            'tenant_present' => ['sometimes', 'boolean'],
            'tenant_observations' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer', 'exists:property_inspection_items,id'],
            'items.*.condition' => ['nullable', Rule::enum(InspectionCondition::class)],
            'items.*.observations' => ['nullable', 'string', 'max:5000'],
            'items.*.requires_maintenance' => ['sometimes', 'boolean'],
        ];
    }
}
