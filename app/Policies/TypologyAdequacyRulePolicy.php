<?php

namespace App\Policies;

use App\Models\TypologyAdequacyRule;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class TypologyAdequacyRulePolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, TypologyAdequacyRule $rule): bool
    {
        return $this->viewAny($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'allocations', 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'create');
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function update(User $user, TypologyAdequacyRule $rule): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function updateBackoffice(User $user, TypologyAdequacyRule $rule): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'update')
            && $this->municipalScope->ownsTypologyAdequacyRule($user, $rule);
    }
}
