<?php

namespace App\Policies;

use App\Enums\MaintenanceRequestStatus;
use App\Models\Contract;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class MaintenanceRequestPolicy
{
    use ChecksMaintenanceAccess;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /*
     * Compatibilidade com os fluxos candidate/tenant existentes.
     * O backoffice deve utilizar apenas as abilities *Backoffice.
     */

    public function viewAny(User $user): bool
    {
        return $this->canViewMaintenance($user);
    }

    public function view(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        if ($user->hasRole('candidate')) {
            return $maintenanceRequest->user_id === $user->id
                || $this->ownsContract(
                    $user,
                    $this->contract($maintenanceRequest),
                );
        }

        return $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateMaintenance($user);
    }

    public function update(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        if ($user->hasRole('candidate')) {
            return $this->view($user, $maintenanceRequest)
                && ! (
                    $this->status($maintenanceRequest)?->isTerminal()
                    ?? true
                );
        }

        return $this->canManageMaintenance($user);
    }

    public function delete(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess(
                $user,
                'maintenance_requests',
                'delete',
            );
    }

    public function approve(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $this->canApproveMaintenance($user);
    }

    public function reject(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess(
                $user,
                'maintenance_requests',
                'reject',
            );
    }

    public function manage(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $this->canManageMaintenance($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'maintenance_requests.view',
        ) && $this->municipalScope
            ->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsMaintenanceRequest(
                $user,
                $maintenanceRequest,
            );
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'maintenance_requests.create',
        ) && $this->municipalScope
            ->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance_requests.update',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function deleteBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance_requests.delete',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function reviewBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance_requests.review',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function scheduleBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance_requests.schedule',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function startBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance_requests.start',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function resolveBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance_requests.resolve',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function rejectBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance_requests.reject',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function closeBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance_requests.close',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    public function cancelBackoffice(
        User $user,
        MaintenanceRequest $maintenanceRequest,
    ): bool {
        return $user->hasPermission(
            'maintenance_requests.cancel',
        ) && $this->municipalScope->ownsMaintenanceRequest(
            $user,
            $maintenanceRequest,
        );
    }

    private function contract(
        MaintenanceRequest $maintenanceRequest,
    ): ?Contract {
        $contract = $maintenanceRequest->leaseContract;

        return $contract instanceof Contract
            ? $contract
            : null;
    }

    private function status(
        MaintenanceRequest $maintenanceRequest,
    ): ?MaintenanceRequestStatus {
        $status = $maintenanceRequest->getAttribute('status');

        if ($status instanceof MaintenanceRequestStatus) {
            return $status;
        }

        return is_string($status)
            ? MaintenanceRequestStatus::tryFrom($status)
            : null;
    }
}
