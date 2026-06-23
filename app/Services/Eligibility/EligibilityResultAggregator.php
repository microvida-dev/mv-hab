<?php

namespace App\Services\Eligibility;

use App\Enums\EligibilityCriterionResult;
use App\Enums\EligibilityResult;
use Illuminate\Support\Collection;

class EligibilityResultAggregator
{
    /**
     * @param  Collection<int, mixed>  $evaluations
     */
    public function aggregate(Collection $evaluations): EligibilityResult
    {
        $applicable = $evaluations->reject(
            fn (array $item) => $item['evaluation']['result'] === EligibilityCriterionResult::NotApplicable,
        );

        if ($applicable->isEmpty()) {
            return EligibilityResult::NotApplicable;
        }

        if ($applicable->contains(fn (array $item) => $item['criterion']->is_mandatory
            && $item['evaluation']['result'] === EligibilityCriterionResult::Failed)) {
            return EligibilityResult::Ineligible;
        }

        if ($applicable->contains(fn (array $item) => $item['criterion']->is_mandatory
            && $item['evaluation']['result'] === EligibilityCriterionResult::InsufficientData)) {
            return EligibilityResult::InsufficientData;
        }

        if ($applicable->contains(fn (array $item) => $item['evaluation']['result'] === EligibilityCriterionResult::RequiresReview)) {
            return EligibilityResult::RequiresReview;
        }

        return EligibilityResult::Eligible;
    }
}
