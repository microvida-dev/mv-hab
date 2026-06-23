<?php

namespace App\Services\BackofficeDashboard;

use App\Models\BackofficeDashboardSnapshot;
use App\Models\User;

class ExecutiveDashboardService
{
    public function __construct(private readonly DashboardMetricAggregator $aggregator) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters, User $actor): array
    {
        return $this->aggregator->aggregate($filters, $actor);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function snapshot(array $filters, User $actor): BackofficeDashboardSnapshot
    {
        $snapshot = new BackofficeDashboardSnapshot([
            'municipality_id' => $filters['municipality_id'] ?? null,
            'program_id' => $filters['program_id'] ?? null,
            'contest_id' => $filters['contest_id'] ?? null,
            'period_start' => $filters['period_start'] ?? null,
            'period_end' => $filters['period_end'] ?? null,
        ]);

        $snapshot->forceFill([
            'snapshot_number' => $this->number(),
            'metrics' => $this->build($filters, $actor),
            'generated_by' => $actor->id,
            'generated_at' => now(),
        ])->save();

        return $snapshot->refresh();
    }

    private function number(): string
    {
        $next = BackofficeDashboardSnapshot::query()->count() + 1;

        do {
            $number = 'DASH-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (BackofficeDashboardSnapshot::query()->where('snapshot_number', $number)->exists());

        return $number;
    }
}
