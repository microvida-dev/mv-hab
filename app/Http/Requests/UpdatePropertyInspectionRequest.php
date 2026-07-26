<?php

namespace App\Http\Requests;

use App\Models\PropertyInspection;
use App\Models\User;

class UpdatePropertyInspectionRequest extends StorePropertyInspectionRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $propertyInspection = $this->route(
            'propertyInspection',
        );

        return $actor instanceof User
            && $propertyInspection instanceof PropertyInspection
            && $actor->can(
                'updateBackoffice',
                $propertyInspection,
            );
    }
}
