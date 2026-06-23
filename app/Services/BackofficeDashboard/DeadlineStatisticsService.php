<?php

namespace App\Services\BackofficeDashboard;

use App\Models\ContestDeadline;

class DeadlineStatisticsService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summary(array $filters = []): array
    {
        $query = ContestDeadline::query();

        if (! empty($filters['contest_id'])) {
            $query->where('contest_id', (int) $filters['contest_id']);
        }

        return [
            'active' => (clone $query)
                ->where(fn ($builder) => $builder->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($builder) => $builder->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->count(),
            'expiring' => (clone $query)
                ->whereBetween('ends_at', [now(), now()->addDays(7)])
                ->count(),
            'expired' => (clone $query)
                ->whereNotNull('ends_at')
                ->where('ends_at', '<', now())
                ->count(),
        ];
    }
}
