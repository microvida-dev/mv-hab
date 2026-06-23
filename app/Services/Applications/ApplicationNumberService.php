<?php

namespace App\Services\Applications;

use App\Models\Application;
use Illuminate\Support\Str;

class ApplicationNumberService
{
    public function generate(Application $application): string
    {
        $contestReference = Str::of($application->contest->code)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->toString();

        return sprintf(
            'CAND-%s-%s-%06d',
            now()->format('Y'),
            $contestReference ?: 'GERAL',
            $application->id,
        );
    }
}
