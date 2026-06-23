<?php

namespace App\Http\Requests;

use App\Enums\AllocationMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunAllocationRequest extends FormRequest
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
            'definitive_list_id' => ['required', 'exists:definitive_lists,id'],
            'allocation_rule_set_id' => ['nullable', 'exists:allocation_rule_sets,id'],
            'allocation_method' => ['nullable', 'string', Rule::in(AllocationMethod::values())],
            'seed' => ['nullable', 'string', 'max:255'],
            'seed_source' => ['nullable', 'string', 'max:255'],
            'algorithm' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
