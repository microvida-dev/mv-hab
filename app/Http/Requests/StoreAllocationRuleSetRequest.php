<?php

namespace App\Http\Requests;

use App\Enums\AllocationMethod;
use App\Enums\AllocationRuleSetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAllocationRuleSetRequest extends FormRequest
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
            'program_id' => ['nullable', 'exists:programs,id', 'required_without:contest_id'],
            'contest_id' => ['nullable', 'exists:contests,id', 'required_without:program_id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', 'string', Rule::in(AllocationRuleSetStatus::values())],
            'allocation_method' => ['required', 'string', Rule::in(AllocationMethod::values())],
            'allow_preferences' => ['boolean'],
            'allow_lottery' => ['boolean'],
            'allow_manual_override' => ['boolean'],
            'requires_acceptance' => ['boolean'],
            'acceptance_deadline_days' => ['required', 'integer', 'min:1', 'max:120'],
            'auto_call_next_on_refusal' => ['boolean'],
            'auto_call_next_on_expiry' => ['boolean'],
            'max_refusals_allowed' => ['nullable', 'integer', 'min:0', 'max:20'],
        ];
    }
}
