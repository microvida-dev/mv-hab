<?php

namespace App\Policies;

use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class MaintenanceAssignmentPolicy
{
    use ChecksMaintenanceAccess;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /*
     * Compatibilidade temporária.
     * O backoffice deve utilizar as abilities *Backoffice.
     */

    public function view(
        User $user,
        MaintenanceAssignment $assignment,
    ): bool {
        return $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMaintenance($user);
    }

    public function update(
        User $user,
        MaintenanceAssignment $assignment,
    ): bool {
        return $this->canManageMaintenance($user);
    }

    public function createBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance.assignments.create',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function cancelBackoffice(
        User $user,
        MaintenanceAssignment $assignment,
    ): bool {
        return $user->hasPermission(
            'maintenance.assignments.cancel',
        ) && $this->municipalScope->ownsMaintenanceAssignment(
            $user,
            $assignment,
        );
    }
}
