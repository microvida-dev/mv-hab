<?php

namespace App\Policies;

use App\Models\LeaseContractValidation;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class LeaseContractValidationPolicy
{
    use ChecksPermissions;

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }

    public function approve(User $user, LeaseContractValidation $leaseContractValidation): bool
    {
        return $this->create($user);
    }

    public function reject(User $user, LeaseContractValidation $leaseContractValidation): bool
    {
        return $this->create($user);
    }
}
