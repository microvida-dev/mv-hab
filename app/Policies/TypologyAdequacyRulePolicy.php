<?php

namespace App\Policies;

use App\Models\TypologyAdequacyRule;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class TypologyAdequacyRulePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, TypologyAdequacyRule $rule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'create');
    }

    public function update(User $user, TypologyAdequacyRule $rule): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }
}
