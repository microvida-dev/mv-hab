<?php

namespace App\Services\Rgpd;

use Illuminate\Support\Str;

class PseudonymizationService
{
    public function token(string|int $value, string $context): string
    {
        return 'psn_'.hash('sha256', $context.'|'.$value.'|'.config('app.key'));
    }

    public function publicCode(): string
    {
        return 'PSN-'.Str::upper(Str::random(10));
    }
}
