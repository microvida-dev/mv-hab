<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ProgramPolicy
{
    use ChecksPermissions;

    private const MODULE = 'programs';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Program $program): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Program $program): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Program $program): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }

    public function publish(User $user, Program $program): bool
    {
        return $this->canAccess($user, self::MODULE, 'publish');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }

    public function viewBackoffice(User $user, Program $program): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'create');
    }

    public function updateBackoffice(User $user, Program $program): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function deleteBackoffice(User $user, Program $program): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'delete');
    }

    public function publishBackoffice(User $user, Program $program): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'publish');
    }
}
