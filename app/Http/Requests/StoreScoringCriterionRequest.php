<?php

namespace App\Http\Requests;

use App\Enums\ScoringCalculationType;
use App\Enums\ScoringOperator;
use App\Models\ScoringCriterion;
use App\Models\ScoringRuleSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScoringCriterionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        /** @var ScoringRuleSet|null $scoringRuleSet */
        $scoringRuleSet = $this->route('scoringRuleSet');

        if ($scoringRuleSet !== null) {
            $this->merge([
                'scoring_rule_set_id' => $scoringRuleSet->id,
            ]);
        }
    }

    public function authorize(): bool
    {
        /** @var ScoringRuleSet|null $scoringRuleSet */
        $scoringRuleSet = $this->route('scoringRuleSet');

        return $this->user()?->can(
            'create',
            [ScoringCriterion::class, $scoringRuleSet]
        ) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scoring_rule_set_id' => [
                'required',
                'exists:scoring_rule_sets,id',
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('scoring_criteria', 'code')
                    ->where(
                        'scoring_rule_set_id',
                        $this->input('scoring_rule_set_id')
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'category' => [
                'required',
                'string',
                'max:100',
            ],

            'target' => [
                'required',
                'string',
                'max:100',
            ],

            'calculation_type' => [
                'required',
                Rule::enum(ScoringCalculationType::class),
            ],

            'operator' => [
                'nullable',
                Rule::enum(ScoringOperator::class),
            ],

            'expected_value' => [
                'nullable',
            ],

            'minimum_value' => [
                'nullable',
                'numeric',
            ],

            'maximum_value' => [
                'nullable',
                'numeric',
            ],

            'points' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'max_points' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'weight' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'requires_manual_review' => [
                'boolean',
            ],

            'is_exclusionary' => [
                'boolean',
            ],

            'is_active' => [
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'success_message' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'failure_message' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'review_message' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
