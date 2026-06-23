<?php

namespace App\Policies;

use App\Models\ContractTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ContractTemplatePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contracts', 'view');
    }

    public function view(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'create');
    }

    public function update(User $user, ContractTemplate $contractTemplate): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function activate(User $user, ContractTemplate $contractTemplate): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }

    public function archive(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->update($user, $contractTemplate);
    }

    public function duplicate(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->create($user);
    }
}
