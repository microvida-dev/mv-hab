<?php

namespace App\Policies;

use App\Models\DrawConvocation;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DrawConvocationPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('candidate')
            ? $this->canAccess($user, 'notifications', 'view')
            : $this->canAccess($user, 'notifications', 'view');
    }

    public function view(User $user, DrawConvocation $convocation): bool
    {
        if ($user->hasRole('candidate')) {
            return $convocation->user_id === $user->id && $this->canAccess($user, 'notifications', 'view');
        }

        return $this->canAccess($user, 'notifications', 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'notifications', 'create');
    }

    public function update(User $user, DrawConvocation $convocation): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'notifications', 'update');
    }
}
