<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\LeaseContractValidation;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class LeaseContractValidationPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function validateBackoffice(User $user, Contract $contract): bool
    {
        return $this->canMutate($user, 'create')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function approveBackoffice(
        User $user,
        LeaseContractValidation $validation,
    ): bool {
        return $this->canMutate($user, 'approve')
            && $this->municipalScope->ownsLeaseContractValidation($user, $validation);
    }

    public function rejectBackoffice(
        User $user,
        LeaseContractValidation $validation,
    ): bool {
        return $this->canMutate($user, 'reject')
            && $this->municipalScope->ownsLeaseContractValidation($user, $validation);
    }

    private function canMutate(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'contracts', "validations.{$action}");
    }
}
