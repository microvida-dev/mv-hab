<?php

namespace App\Http\Requests\Reporting;

use App\Enums\IndicatorCategory;
use App\Enums\IndicatorValueType;
use App\Models\IndicatorDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndicatorDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', IndicatorDefinition::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'alpha_dash', 'max:150', Rule::unique('indicator_definitions', 'code')->ignore($this->route('indicatorDefinition'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::enum(IndicatorCategory::class)],
            'value_type' => ['required', Rule::enum(IndicatorValueType::class)],
            'calculation_service' => ['required', 'string', 'max:255'],
            'calculation_method' => ['required', 'string', 'max:150'],
            'required_permission' => ['nullable', 'string', 'max:150'],
            'is_sensitive' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
