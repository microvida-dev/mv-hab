<?php

namespace App\Http\Requests;

use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityOperator;
use App\Models\EligibilityCriterion;
use App\Models\EligibilityRuleSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEligibilityCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ruleSet = $this->ruleSetOrNull();

        if (! $ruleSet instanceof EligibilityRuleSet) {
            return false;
        }

        return $this->user()?->can('create', [
            EligibilityCriterion::class,
            $ruleSet,
        ]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ruleSet = $this->ruleSet();

        return [
            'code' => [
                'required',
                'string',
                'max:150',
                Rule::unique('eligibility_criteria', 'code')
                    ->where('eligibility_rule_set_id', $ruleSet->id)
                    ->whereNull('deleted_at'),
            ],

            'name' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'string', 'max:5000'],

            'category' => [
                'required',
                Rule::enum(EligibilityCriterionCategory::class),
            ],

            'target' => [
                'required',
                Rule::in($this->targets()),
            ],

            'operator' => [
                'required',
                Rule::enum(EligibilityOperator::class),
            ],

            'expected_value' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'minimum_value' => [
                'nullable',
                'numeric',
            ],

            'maximum_value' => [
                'nullable',
                'numeric',
                'gte:minimum_value',
            ],

            'unit' => [
                'nullable',
                'string',
                'max:50',
            ],

            'is_mandatory' => [
                'sometimes',
                'boolean',
            ],

            'requires_manual_review' => [
                'sometimes',
                'boolean',
            ],

            'failure_message' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'success_message' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'review_message' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function targets(): array
    {
        return [
            'adhesion_registration',
            'household',
            'household_member',
            'income_records',
            'current_housing_situation',
            'documents',
            'application',
            'contest',
            'program',
            'calculated_value',
            'manual',
        ];
    }

    public function ruleSet(): EligibilityRuleSet
    {
        $ruleSet = $this->ruleSetOrNull();

        abort_unless($ruleSet instanceof EligibilityRuleSet, 404);

        return $ruleSet;
    }

    private function ruleSetOrNull(): ?EligibilityRuleSet
    {
        $ruleSet = $this->route('eligibilityRuleSet');

        if ($ruleSet instanceof EligibilityRuleSet) {
            return $ruleSet;
        }

        $criterion = $this->route('eligibilityCriterion');

        if ($criterion instanceof EligibilityCriterion) {
            $criterion->loadMissing('ruleSet');

            $ruleSet = $criterion->ruleSet;

            return $ruleSet instanceof EligibilityRuleSet ? $ruleSet : null;
        }

        if (is_numeric($ruleSet)) {
            return EligibilityRuleSet::query()->find((int) $ruleSet);
        }

        return null;
    }
}
