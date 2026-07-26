<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\LeaseContractSignature;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class LeaseContractSignaturePolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function update(User $user, LeaseContractSignature $leaseContractSignature): bool
    {
        return $this->create($user);
    }

    public function signBackoffice(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'contracts', 'sign')
            && $this->municipalScope->ownsContract($user, $contract);
    }
}
