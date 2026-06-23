<?php

namespace App\Services\Reporting;

use App\Models\IndicatorDefinition;
use App\Models\IndicatorSnapshot;
use App\Models\User;
use Throwable;

class IndicatorCalculationService
{
    public function __construct(
        private readonly IndicatorRegistry $registry,
        private readonly ReportFilterService $filters,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{status: string, value: mixed, calculated_at: mixed, message?: string}
     */
    public function calculate(IndicatorDefinition $definition, array $filters, User $user, bool $snapshot = false): array
    {
        $definitionFilters = $definition->getAttribute('default_filters');
        $defaultFilters = is_array($definitionFilters) ? $definitionFilters : [];
        $normalized = array_merge($defaultFilters, $this->filters->normalize($filters));

        if ($definition->required_permission && ! $user->hasPermission($definition->required_permission)) {
            return ['status' => 'restricted', 'value' => null, 'calculated_at' => now()];
        }

        try {
            $value = $this->registry->calculate($definition, $normalized);
            $result = ['status' => 'available', 'value' => $value, 'calculated_at' => now()];

            if ($snapshot) {
                $indicatorSnapshot = new IndicatorSnapshot;
                $indicatorSnapshot->forceFill([
                    'indicator_definition_id' => $definition->getKey(),
                    'value_numeric' => is_numeric($value) ? $value : null,
                    'value_json' => is_array($value) ? $value : null,
                    'filters' => $normalized,
                    'filters_hash' => $this->filters->hash($normalized),
                    'status' => 'available',
                    'calculated_at' => now(),
                    'calculated_by' => $user->getKey(),
                ])->save();
            }

            return $result;
        } catch (Throwable $exception) {
            report($exception);

            return ['status' => 'error', 'value' => null, 'calculated_at' => now(), 'message' => 'Indicador temporariamente indisponível.'];
        }
    }
}
