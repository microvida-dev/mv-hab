<?php

namespace App\Http\Requests;

use App\Models\SecurityAlertRule;

class UpdateSecurityAlertRuleRequest extends StoreSecurityAlertRuleRequest
{
    public function authorize(): bool
    {
        $rule = $this->route('securityAlertRule');

        return $rule instanceof SecurityAlertRule
            && ($this->user()?->can('update', $rule) ?? false);
    }
}
