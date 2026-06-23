<?php

namespace App\Http\Requests;

use App\Enums\ScoringOperator;
use App\Models\ScoringCriterion;
use App\Models\ScoringRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreScoringRuleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $scoringCriterion = $this->route('scoringCriterion');

        if ($scoringCriterion instanceof ScoringCriterion) {
            $this->merge([
                'scoring_criterion_id' => $scoringCriterion->id,
            ]);
        }
    }

    public function authorize(): bool
    {
        $scoringCriterion = $this->route('scoringCriterion');

        if (! $scoringCriterion instanceof ScoringCriterion) {
            return false;
        }

        return $this->user()?->can(
            'create',
            [ScoringRule::class, $scoringCriterion]
        ) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scoring_criterion_id' => ['required', 'exists:scoring_criteria,id'],
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
