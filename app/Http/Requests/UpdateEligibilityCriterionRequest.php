<?php

namespace App\Http\Requests;

use App\Models\EligibilityCriterion;
use Illuminate\Validation\Rule;

class UpdateEligibilityCriterionRequest extends StoreEligibilityCriterionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('eligibilityCriterion')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $criterion = $this->route('eligibilityCriterion');

        if (! $criterion instanceof EligibilityCriterion) {
            abort(404);
        }

        $rules['code'] = [
            'required',
            'string',
            'max:150',
            Rule::unique('eligibility_criteria', 'code')
                ->where(
                    'eligibility_rule_set_id',
                    $criterion->eligibility_rule_set_id
                )
                ->whereNull('deleted_at')
                ->ignore($criterion),
        ];

        return $rules;
    }
}
