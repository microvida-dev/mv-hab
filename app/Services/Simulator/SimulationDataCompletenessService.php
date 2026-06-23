<?php

namespace App\Services\Simulator;

class SimulationDataCompletenessService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{score: float, missing_fields: list<string>, complete: bool}
     */
    public function evaluate(array $input): array
    {
        $required = [
            'household_members_count',
            'adults_count',
            'monthly_income',
            'housing_status',
        ];

        $missing = [];

        foreach ($required as $field) {
            if (! array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '') {
                $missing[] = $field;
            }
        }

        $score = round(((count($required) - count($missing)) / count($required)) * 100, 2);

        return [
            'score' => $score,
            'missing_fields' => $missing,
            'complete' => $score >= 100.0,
        ];
    }
}
