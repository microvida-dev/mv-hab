<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ContractPolicy
{
    use ChecksPermissions;

    private const MODULE = 'contracts';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->hasRole('candidate')
            ? $contract->user_id === $user->id && $this->canAccess($user, self::MODULE, 'view')
            : $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'delete');
    }

    public function approve(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'approve');
    }

    public function issue(User $user, Contract $contract): bool
    {
        return $this->update($user, $contract);
    }

    public function activate(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'approve');
    }

    public function sign(User $user, Contract $contract): bool
    {
        return $this->update($user, $contract);
    }

    public function generateDocument(User $user, Contract $contract): bool
    {
        return $this->update($user, $contract);
    }
}
