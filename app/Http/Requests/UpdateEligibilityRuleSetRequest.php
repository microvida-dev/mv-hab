<?php

namespace App\Http\Requests;

class UpdateEligibilityRuleSetRequest extends StoreEligibilityRuleSetRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('eligibilityRuleSet')) ?? false;
    }
}
