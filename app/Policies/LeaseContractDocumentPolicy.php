<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\LeaseContractDocument;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class LeaseContractDocumentPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function view(User $user, LeaseContractDocument $leaseContractDocument): bool
    {
        $contract = $leaseContractDocument->leaseContract;

        return $user->hasRole('candidate')
            ? $contract instanceof Contract && $contract->user_id === $user->id && $this->canAccess($user, 'contracts', 'view')
            : $this->canAccess($user, 'contracts', 'view');
    }

    public function download(User $user, LeaseContractDocument $leaseContractDocument): bool
    {
        return $this->view($user, $leaseContractDocument);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function update(User $user, LeaseContractDocument $leaseContractDocument): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function downloadBackoffice(
        User $user,
        LeaseContractDocument $leaseContractDocument,
    ): bool {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'download')
            && $this->municipalScope->ownsLeaseContractDocument($user, $leaseContractDocument);
    }
}
