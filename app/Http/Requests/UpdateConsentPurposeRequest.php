<?php

namespace App\Http\Requests;

use App\Models\User;

class UpdateConsentPurposeRequest extends StoreConsentPurposeRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->municipality_id !== null
            && $user->hasPermission('privacy.update');
    }
}
