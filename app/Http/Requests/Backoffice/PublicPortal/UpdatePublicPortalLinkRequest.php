<?php

namespace App\Http\Requests\Backoffice\PublicPortal;

use Illuminate\Support\Facades\Gate;

class UpdatePublicPortalLinkRequest extends StorePublicPortalLinkRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'updateBackoffice',
            $this->route('link'),
        );
    }
}
