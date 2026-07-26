<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class MaintenanceInterventionPolicy
{
    use ChecksMaintenanceAccess;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /*
     * Compatibilidade com o portal tenant.
     * O backoffice deve utilizar as abilities *Backoffice.
     */

    public function view(
        User $user,
        MaintenanceIntervention $intervention,
    ): bool {
        return $user->hasRole('candidate')
            ? $this->ownsContract(
                $user,
                $this->contract($intervention),
            )
            : $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMaintenance($user);
    }

    public function update(
        User $user,
        MaintenanceIntervention $intervention,
    ): bool {
        return $this->canManageMaintenance($user);
    }

    public function viewBackoffice(
        User $user,
        MaintenanceIntervention $intervention,
    ): bool {
        return $user->hasPermission(
            'maintenance.interventions.view',
        ) && $this->municipalScope
            ->ownsMaintenanceIntervention(
                $user,
                $intervention,
            );
    }

    public function createBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance.interventions.create',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function startBackoffice(
        User $user,
        MaintenanceIntervention $intervention,
    ): bool {
        return $user->hasPermission(
            'maintenance.interventions.start',
        ) && $this->municipalScope
            ->ownsMaintenanceIntervention(
                $user,
                $intervention,
            );
    }

    public function completeBackoffice(
        User $user,
        MaintenanceIntervention $intervention,
    ): bool {
        return $user->hasPermission(
            'maintenance.interventions.complete',
        ) && $this->municipalScope
            ->ownsMaintenanceIntervention(
                $user,
                $intervention,
            );
    }

    public function cancelBackoffice(
        User $user,
        MaintenanceIntervention $intervention,
    ): bool {
        return $user->hasPermission(
            'maintenance.interventions.cancel',
        ) && $this->municipalScope
            ->ownsMaintenanceIntervention(
                $user,
                $intervention,
            );
    }

    private function contract(
        MaintenanceIntervention $intervention,
    ): ?Contract {
        $contract = $intervention->leaseContract;

        return $contract instanceof Contract
            ? $contract
            : null;
    }
}
