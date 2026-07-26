<?php

namespace App\Http\Requests;

use App\Models\RentRuleSet;

class UpdateRentRuleSetRequest extends StoreRentRuleSetRequest
{
    public function authorize(): bool
    {
        $ruleSet = $this->route('rentRuleSet');

        return $ruleSet instanceof RentRuleSet
            && $this->user()?->can('updateBackoffice', $ruleSet) === true;
    }
}
