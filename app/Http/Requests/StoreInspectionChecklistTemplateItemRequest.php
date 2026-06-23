<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionChecklistTemplateItemRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:80'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'area' => ['nullable', 'string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
