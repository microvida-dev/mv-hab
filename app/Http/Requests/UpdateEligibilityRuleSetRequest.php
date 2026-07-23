<?php

namespace App\Http\Requests;

class UpdateEligibilityRuleSetRequest extends StoreEligibilityRuleSetRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'updateBackoffice',
            $this->route('eligibilityRuleSet'),
        ) ?? false;
    }
}
