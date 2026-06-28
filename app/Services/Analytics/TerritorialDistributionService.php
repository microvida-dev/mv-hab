<?php

namespace App\Services\Analytics;

use App\Data\Analytics\ChartDatasetData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TerritorialDistributionService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{type: string, title: string, description: string, items: list<array{label: string, value: int|float, description?: string}>, total: int|float}
     */
    public function applicationsByParish(array $filters): array
    {
        if (
            ! Schema::hasTable('applications')
            || ! Schema::hasTable('current_housing_situations')
            || ! Schema::hasColumn('applications', 'current_housing_situation_id')
            || ! Schema::hasColumn('current_housing_situations', 'current_parish')
        ) {
            return (new ChartDatasetData('bar', 'Distribuição territorial', 'Candidaturas por freguesia.', []))->toArray();
        }

        $query = DB::table('applications')
            ->join('current_housing_situations', 'current_housing_situations.id', '=', 'applications.current_housing_situation_id')
            ->whereNotNull('current_housing_situations.current_parish');

        foreach (['program_id', 'contest_id', 'status'] as $column) {
            if (isset($filters[$column]) && Schema::hasColumn('applications', $column)) {
                $query->where('applications.'.$column, $filters[$column]);
            }
        }

        $items = array_values($query
            ->selectRaw('current_housing_situations.current_parish as label, COUNT(*) as total')
            ->groupBy('current_housing_situations.current_parish')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'value' => (int) $row->total,
            ])
            ->all());

        return (new ChartDatasetData('bar', 'Distribuição territorial', 'Candidaturas agregadas por freguesia.', $items))->toArray();
    }
}
