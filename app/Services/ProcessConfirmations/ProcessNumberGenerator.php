<?php

namespace App\Services\ProcessConfirmations;

use App\Models\Application;
use App\Models\ProcessConfirmation;
use Illuminate\Support\Str;

class ProcessNumberGenerator
{
    public function generate(Application $application): string
    {
        $contestReference = Str::of((string) data_get($application->getRelationValue('contest'), 'code'))
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->toString();
        $contestReference = $contestReference !== '' ? $contestReference : 'GERAL';
        $next = ProcessConfirmation::withTrashed()->count() + 1;

        do {
            $number = sprintf('HAB-%s-%s-%06d', now()->format('Y'), $contestReference, $next);
            $next++;
        } while (ProcessConfirmation::withTrashed()->where('process_number', $number)->exists());

        return $number;
    }
}
