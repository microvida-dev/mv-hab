<?php

namespace App\Http\Requests;

use App\Enums\RentCalculationMethod;
use App\Enums\RentRuleSetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentRuleSetRequest extends FormRequest
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
            'program_id' => ['nullable', 'required_without:contest_id', 'exists:programs,id'],
            'contest_id' => ['nullable', 'required_without:program_id', 'exists:contests,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::enum(RentRuleSetStatus::class)],
            'calculation_method' => ['required', Rule::enum(RentCalculationMethod::class)],
            'income_period' => ['required', 'string', 'max:100'],
            'income_basis' => ['required', 'string', 'max:100'],
            'effort_rate_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_rent' => ['nullable', 'numeric', 'min:0'],
            'maximum_rent' => ['nullable', 'numeric', 'min:0', 'gte:minimum_rent'],
            'minimum_effort_rate_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'maximum_effort_rate_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deposit_months' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'minimum_deposit' => ['nullable', 'numeric', 'min:0'],
            'maximum_deposit' => ['nullable', 'numeric', 'min:0', 'gte:minimum_deposit'],
            'rounding_mode' => ['nullable', 'string', 'max:100'],
            'rounding_precision' => ['nullable', 'integer', 'min:0', 'max:2'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'requires_manual_approval' => ['sometimes', 'boolean'],
            'allow_manual_override' => ['sometimes', 'boolean'],
        ];
    }
}
