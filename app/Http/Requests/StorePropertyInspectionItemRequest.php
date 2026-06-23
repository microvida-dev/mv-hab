<?php

namespace App\Http\Requests;

use App\Enums\InspectionCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyInspectionItemRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', Rule::enum(InspectionCondition::class)],
            'observations' => ['nullable', 'string', 'max:5000'],
            'requires_maintenance' => ['sometimes', 'boolean'],
        ];
    }
}
