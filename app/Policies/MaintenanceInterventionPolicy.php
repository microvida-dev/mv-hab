<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\MaintenanceIntervention;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class MaintenanceInterventionPolicy
{
    use ChecksMaintenanceAccess;

    public function view(User $user, MaintenanceIntervention $intervention): bool
    {
        return $user->hasRole('candidate')
            ? $this->ownsContract($user, $this->contract($intervention))
            : $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMaintenance($user);
    }

    public function update(User $user, MaintenanceIntervention $intervention): bool
    {
        return $this->canManageMaintenance($user);
    }

    private function contract(MaintenanceIntervention $intervention): ?Contract
    {
        $contract = $intervention->leaseContract;

        return $contract instanceof Contract ? $contract : null;
    }
}
