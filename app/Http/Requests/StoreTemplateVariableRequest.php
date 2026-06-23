<?php

namespace App\Http\Requests;

use App\Enums\TemplateVariableType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateVariableRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:150', Rule::unique('template_variables')->ignore($this->route('templateVariable'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'variable_type' => ['required', Rule::in(TemplateVariableType::values())],
            'source_key' => ['nullable', 'string', 'max:255'],
            'example_value' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['sometimes', 'boolean'],
            'is_sensitive' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
