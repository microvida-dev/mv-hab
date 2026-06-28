<?php

namespace App\Services\Analytics;

use App\Models\User;

class ExecutiveDashboardService
{
    public function __construct(private readonly DashboardAnalyticsService $analytics) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters): array
    {
        return $this->analytics->executive($user, $filters);
    }
}
