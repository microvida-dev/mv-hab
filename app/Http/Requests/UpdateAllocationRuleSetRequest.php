<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateAllocationRuleSetRequest extends StoreAllocationRuleSetRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'updateBackoffice',
            $this->route('allocationRuleSet'),
        );
    }
}
