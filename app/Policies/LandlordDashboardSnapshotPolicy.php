<?php

namespace App\Policies;

use App\Models\LandlordDashboardSnapshot;
use App\Models\User;

class LandlordDashboardSnapshotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager', 'auditor']);
    }

    public function view(User $user, LandlordDashboardSnapshot $landlordDashboardSnapshot): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager']);
    }
}
