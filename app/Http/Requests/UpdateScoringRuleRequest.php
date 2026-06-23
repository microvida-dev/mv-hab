<?php

namespace App\Http\Requests;

use App\Enums\ScoringOperator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScoringRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('scoringRule')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'operator' => ['nullable', 'in:'.implode(',', ScoringOperator::values())],
            'value' => ['nullable'],
            'minimum_value' => ['nullable', 'numeric'],
            'maximum_value' => ['nullable', 'numeric'],
            'points' => ['required', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
