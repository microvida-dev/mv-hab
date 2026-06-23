<?php

namespace App\Policies;

use App\Enums\MaintenanceRequestStatus;
use App\Models\Contract;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class MaintenanceRequestPolicy
{
    use ChecksMaintenanceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewMaintenance($user);
    }

    public function view(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        if ($user->hasRole('candidate')) {
            return $maintenanceRequest->user_id === $user->id
                || $this->ownsContract($user, $this->contract($maintenanceRequest));
        }

        return $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateMaintenance($user);
    }

    public function update(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        if ($user->hasRole('candidate')) {
            return $this->view($user, $maintenanceRequest) && ! ($this->status($maintenanceRequest)?->isTerminal() ?? true);
        }

        return $this->canManageMaintenance($user);
    }

    public function delete(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'maintenance_requests', 'delete');
    }

    public function approve(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        return $this->canApproveMaintenance($user);
    }

    public function reject(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'maintenance_requests', 'reject');
    }

    public function manage(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        return $this->canManageMaintenance($user);
    }

    private function contract(MaintenanceRequest $maintenanceRequest): ?Contract
    {
        $contract = $maintenanceRequest->leaseContract;

        return $contract instanceof Contract ? $contract : null;
    }

    private function status(MaintenanceRequest $maintenanceRequest): ?MaintenanceRequestStatus
    {
        $status = $maintenanceRequest->getAttribute('status');

        if ($status instanceof MaintenanceRequestStatus) {
            return $status;
        }

        return is_string($status) ? MaintenanceRequestStatus::tryFrom($status) : null;
    }
}
