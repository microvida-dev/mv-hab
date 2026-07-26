<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\VisitAvailability;

class UpdateVisitAvailabilityRequest extends StoreVisitAvailabilityRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $availability = $this->route('visitAvailability');

        return $actor instanceof User
            && $availability instanceof VisitAvailability
            && $actor->can(
                'updateBackoffice',
                $availability,
            );
    }
}
