<?php

namespace App\Policies;

use App\Models\ContractClause;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ContractClausePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contracts', 'view');
    }

    public function view(User $user, ContractClause $contractClause): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'create');
    }

    public function update(User $user, ContractClause $contractClause): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function activate(User $user, ContractClause $contractClause): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }

    public function archive(User $user, ContractClause $contractClause): bool
    {
        return $this->update($user, $contractClause);
    }
}
