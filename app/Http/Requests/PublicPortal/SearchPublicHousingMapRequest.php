<?php

namespace App\Http\Requests\PublicPortal;

class SearchPublicHousingMapRequest extends SearchHousingOfferRequest
{
    public function expectsJson(): bool
    {
        return true;
    }
}
