<?php

namespace App\Http\Requests;

use App\Models\RentRule;

class UpdateRentRuleRequest extends StoreRentRuleRequest
{
    public function authorize(): bool
    {
        $rule = $this->route('rentRule');

        return $rule instanceof RentRule
            && $this->user()?->can('updateBackoffice', $rule) === true;
    }
}
