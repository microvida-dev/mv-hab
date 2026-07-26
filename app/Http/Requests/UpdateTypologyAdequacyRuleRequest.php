<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateTypologyAdequacyRuleRequest extends StoreTypologyAdequacyRuleRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'updateBackoffice',
            $this->route('typologyAdequacyRule'),
        );
    }
}
