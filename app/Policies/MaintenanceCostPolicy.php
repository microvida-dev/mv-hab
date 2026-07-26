<?php

namespace App\Policies;

use App\Models\MaintenanceCost;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class MaintenanceCostPolicy
{
    use ChecksMaintenanceAccess;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /*
     * Compatibilidade temporária.
     * O backoffice deve utilizar as abilities *Backoffice.
     */

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canViewMaintenance($user);
    }

    public function view(
        User $user,
        MaintenanceCost $cost,
    ): bool {
        return ! $user->hasRole('candidate')
            && $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMaintenance($user);
    }

    public function approve(
        User $user,
        MaintenanceCost $cost,
    ): bool {
        return $this->canApproveMaintenance($user);
    }

    public function reject(
        User $user,
        MaintenanceCost $cost,
    ): bool {
        return $this->canApproveMaintenance($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'maintenance.costs.view',
        ) && $this->municipalScope
            ->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        MaintenanceCost $cost,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope
                ->ownsMaintenanceCost($user, $cost);
    }

    public function createBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance.costs.create',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function approveBackoffice(
        User $user,
        MaintenanceCost $cost,
    ): bool {
        return $user->hasPermission(
            'maintenance.costs.approve',
        ) && $this->municipalScope
            ->ownsMaintenanceCost($user, $cost);
    }

    public function rejectBackoffice(
        User $user,
        MaintenanceCost $cost,
    ): bool {
        return $user->hasPermission(
            'maintenance.costs.reject',
        ) && $this->municipalScope
            ->ownsMaintenanceCost($user, $cost);
    }
}
