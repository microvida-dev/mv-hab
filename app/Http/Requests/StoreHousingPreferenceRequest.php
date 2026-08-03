<?php

namespace App\Http\Requests;

class StoreHousingPreferenceRequest extends UpdateHousingPreferenceRequest
{
    protected function requiresMinimum(): bool
    {
        return true;
    }
}
