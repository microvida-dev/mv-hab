<?php

namespace App\Policies;

use App\Models\ContestClosure;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ContestClosurePolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'contests', 'view');
    }

    public function view(User $user, ContestClosure $closure): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contests', 'approve');
    }

    public function viewBackoffice(User $user, ContestClosure $closure): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'allocations', 'view')
            && $this->municipalScope->ownsContestClosure($user, $closure);
    }
}
