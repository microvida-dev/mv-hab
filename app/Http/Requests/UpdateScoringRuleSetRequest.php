<?php

namespace App\Http\Requests;

class UpdateScoringRuleSetRequest extends StoreScoringRuleSetRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'updateBackoffice',
            $this->route('scoringRuleSet'),
        ) ?? false;
    }
}
