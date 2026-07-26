<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateContestHousingUnitRequest extends StoreContestHousingUnitRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'updateBackoffice',
            $this->route('contestHousingUnit'),
        );
    }
}
