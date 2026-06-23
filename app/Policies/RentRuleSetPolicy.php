<?php

namespace App\Policies;

use App\Models\RentRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RentRuleSetPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contracts', 'view');
    }

    public function view(User $user, RentRuleSet $rentRuleSet): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'create');
    }

    public function update(User $user, RentRuleSet $rentRuleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function activate(User $user, RentRuleSet $rentRuleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }

    public function archive(User $user, RentRuleSet $rentRuleSet): bool
    {
        return $this->update($user, $rentRuleSet);
    }

    public function duplicate(User $user, RentRuleSet $rentRuleSet): bool
    {
        return $this->create($user);
    }
}
