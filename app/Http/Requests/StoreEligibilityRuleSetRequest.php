<?php

namespace App\Http\Requests;

use App\Enums\EligibilityRuleSetStatus;
use App\Models\EligibilityRuleSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEligibilityRuleSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EligibilityRuleSet::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['nullable', 'integer', 'exists:programs,id', 'required_without:contest_id'],
            'contest_id' => ['nullable', 'integer', 'exists:contests,id', 'required_without:program_id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(EligibilityRuleSetStatus::class)],
            'is_default' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
