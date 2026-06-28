<?php

namespace App\Services\Analytics;

use App\Data\Analytics\ChartDatasetData;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class TrendAnalysisService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{type: string, title: string, description: string, items: list<array{label: string, value: int|float, description?: string}>, total: int|float}
     */
    public function monthlyApplications(array $filters): array
    {
        return $this->monthlyCount(
            'applications',
            'created_at',
            'Evolução mensal de candidaturas',
            'Candidaturas registadas por mês, sem dados pessoais.',
            $filters,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{type: string, title: string, description: string, items: list<array{label: string, value: int|float, description?: string}>, total: int|float}
     */
    public function monthlyWorkTasks(array $filters): array
    {
        return $this->monthlyCount(
            'work_tasks',
            'created_at',
            'Evolução mensal de tarefas',
            'Tarefas municipais registadas por mês.',
            $filters,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{type: string, title: string, description: string, items: list<array{label: string, value: int|float, description?: string}>, total: int|float}
     */
    private function monthlyCount(string $table, string $dateColumn, string $title, string $description, array $filters): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $dateColumn)) {
            return (new ChartDatasetData('line', $title, $description, []))->toArray();
        }

        $query = DB::table($table)
            ->selectRaw($this->monthSelectExpression($table, $dateColumn))
            ->whereNotNull($table.'.'.$dateColumn);

        $this->applyFilters($query, $table, $dateColumn, $filters);

        $raw = $query
            ->groupBy('period')
            ->orderBy('period')
            ->limit(24)
            ->pluck('total', 'period')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        $start = CarbonImmutable::now()->startOfMonth()->subMonths(5);
        $items = [];

        for ($month = 0; $month < 6; $month++) {
            $date = $start->addMonths($month);
            $key = $date->format('Y-m');
            $items[] = [
                'label' => $date->translatedFormat('M Y'),
                'value' => (int) ($raw[$key] ?? 0),
            ];
        }

        return (new ChartDatasetData('line', $title, $description, $items))->toArray();
    }

    /**
     * @return literal-string
     */
    private function monthSelectExpression(string $table, string $dateColumn): string
    {
        if ($dateColumn !== 'created_at') {
            throw new InvalidArgumentException('Unsupported analytics trend date column.');
        }

        $sqlite = DB::connection()->getDriverName() === 'sqlite';

        return match ($table) {
            'applications' => $sqlite
                ? "strftime('%Y-%m', applications.created_at) as period, COUNT(*) as total"
                : "DATE_FORMAT(applications.created_at, '%Y-%m') as period, COUNT(*) as total",
            'work_tasks' => $sqlite
                ? "strftime('%Y-%m', work_tasks.created_at) as period, COUNT(*) as total"
                : "DATE_FORMAT(work_tasks.created_at, '%Y-%m') as period, COUNT(*) as total",
            default => throw new InvalidArgumentException('Unsupported analytics trend table.'),
        };
    }

    /**
     * @param  Builder  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters($query, string $table, string $dateColumn, array $filters): void
    {
        foreach (['program_id', 'contest_id', 'status'] as $column) {
            if (isset($filters[$column]) && Schema::hasColumn($table, $column)) {
                $query->where($table.'.'.$column, $filters[$column]);
            }
        }

        if (isset($filters['date_from'])) {
            $query->whereDate($table.'.'.$dateColumn, '>=', (string) $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate($table.'.'.$dateColumn, '<=', (string) $filters['date_to']);
        }
    }
}
