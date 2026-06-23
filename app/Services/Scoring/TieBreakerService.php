<?php

namespace App\Services\Scoring;

use App\Models\Application;
use App\Models\ScoringRuleSet;
use App\Models\TieBreakerRule;

class TieBreakerService
{
    public function __construct(private readonly ScoringDataProvider $dataProvider) {}

    /**
     * @return array<int, array{code: string, name: string, target: string, direction: string, value: bool|float|int|string|null}>
     */
    public function valuesFor(Application $application, ScoringRuleSet $ruleSet): array
    {
        $ruleSet->loadMissing('tieBreakerRules');
        $context = $this->dataProvider->forApplication($application);

        return $ruleSet->tieBreakerRules
            ->where('is_active', true)
            ->map(function (TieBreakerRule $rule) use ($context): array {
                $value = $context['values'][$rule->code]['value']
                    ?? $context['values'][$rule->target]['value']
                    ?? null;

                return [
                    'code' => $rule->code,
                    'name' => $rule->name,
                    'target' => $rule->target,
                    'direction' => $rule->direction->value,
                    'value' => is_scalar($value) || $value === null ? $value : null,
                ];
            })
            ->values()
            ->all();
    }
}
