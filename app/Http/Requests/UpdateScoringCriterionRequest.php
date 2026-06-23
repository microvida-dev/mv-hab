<?php

namespace App\Http\Requests;

use App\Enums\ScoringCalculationType;
use App\Enums\ScoringOperator;
use App\Models\ScoringCriterion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScoringCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $criterion = $this->route('scoringCriterion');

        return $criterion instanceof ScoringCriterion
            && ($this->user()?->can('update', $criterion) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $criterion = $this->route('scoringCriterion');

        if (! $criterion instanceof ScoringCriterion) {
            abort(404);
        }

        return [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('scoring_criteria', 'code')
                    ->where('scoring_rule_set_id', $criterion->scoring_rule_set_id)
                    ->ignore($criterion->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'category' => ['required', 'string', 'max:100'],
            'target' => ['required', 'string', 'max:100'],
            'calculation_type' => ['required', 'in:'.implode(',', ScoringCalculationType::values())],
            'operator' => ['nullable', 'in:'.implode(',', ScoringOperator::values())],
            'expected_value' => ['nullable'],
            'minimum_value' => ['nullable', 'numeric'],
            'maximum_value' => ['nullable', 'numeric'],
            'points' => ['nullable', 'numeric', 'min:0'],
            'max_points' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'requires_manual_review' => ['boolean'],
            'is_exclusionary' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'success_message' => ['nullable', 'string', 'max:1000'],
            'failure_message' => ['nullable', 'string', 'max:1000'],
            'review_message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
