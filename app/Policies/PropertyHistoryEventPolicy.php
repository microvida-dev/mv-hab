<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\PropertyHistoryEvent;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class PropertyHistoryEventPolicy
{
    use ChecksMaintenanceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewMaintenance($user);
    }

    public function view(User $user, PropertyHistoryEvent $event): bool
    {
        if ($user->hasRole('candidate')) {
            return $event->visible_to_tenant && $this->ownsContract($user, $this->contract($event));
        }

        return $this->canViewMaintenance($user);
    }

    private function contract(PropertyHistoryEvent $event): ?Contract
    {
        $contract = $event->leaseContract;

        return $contract instanceof Contract ? $contract : null;
    }
}
