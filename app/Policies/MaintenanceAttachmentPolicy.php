<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class MaintenanceAttachmentPolicy
{
    use ChecksMaintenanceAccess;

    public function view(User $user, MaintenanceAttachment $attachment): bool
    {
        if ($user->hasRole('candidate')) {
            $request = $attachment->maintenanceRequest;

            return $attachment->visible_to_tenant && (
                ($request instanceof MaintenanceRequest && $request->user_id === $user->id)
                || $this->ownsContract($user, $this->requestContract($request instanceof MaintenanceRequest ? $request : null))
            );
        }

        return $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateMaintenance($user);
    }

    public function download(User $user, MaintenanceAttachment $attachment): bool
    {
        return $this->view($user, $attachment);
    }

    private function requestContract(?MaintenanceRequest $request): ?Contract
    {
        $contract = $request?->leaseContract;

        return $contract instanceof Contract ? $contract : null;
    }
}
