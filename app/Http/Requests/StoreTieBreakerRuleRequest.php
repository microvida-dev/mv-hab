<?php

namespace App\Http\Requests;

use App\Enums\TieBreakerDirection;
use App\Models\ScoringRuleSet;
use App\Models\TieBreakerRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTieBreakerRuleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        /** @var ScoringRuleSet|null $scoringRuleSet */
        $scoringRuleSet = $this->route('scoringRuleSet');

        if ($scoringRuleSet instanceof ScoringRuleSet) {
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
            [TieBreakerRule::class, $scoringRuleSet]
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
                Rule::unique('tie_breaker_rules', 'code')
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

            'target' => [
                'required',
                'string',
                'max:100',
            ],

            'direction' => [
                'required',
                'in:'.implode(',', TieBreakerDirection::values()),
            ],

            'priority_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }
}
