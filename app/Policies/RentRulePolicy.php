<?php

namespace App\Policies;

use App\Models\RentRule;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RentRulePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contracts', 'view');
    }

    public function view(User $user, RentRule $rentRule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'create');
    }

    public function update(User $user, RentRule $rentRule): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }
}
