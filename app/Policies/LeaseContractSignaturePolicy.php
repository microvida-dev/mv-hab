<?php

namespace App\Policies;

use App\Models\LeaseContractSignature;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class LeaseContractSignaturePolicy
{
    use ChecksPermissions;

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function update(User $user, LeaseContractSignature $leaseContractSignature): bool
    {
        return $this->create($user);
    }
}
