<?php

namespace App\Services\Reporting;

use App\Models\User;

class OperationalDashboardService
{
    public function __construct(private readonly DashboardService $dashboards) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters): array
    {
        return $this->dashboards->render('operational', $user, $filters);
    }
}
