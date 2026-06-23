<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ApplicationPolicy
{
    use ChecksPermissions;

    private const MODULE = 'applications';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Application $application): bool
    {
        if ($user->hasRole('candidate')) {
            return $application->user_id === $user->id
                && $this->canAccess($user, self::MODULE, 'view');
        }

        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Application $application): bool
    {
        return $application->user_id === $user->id
            && $application->isEditable()
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function submit(User $user, Application $application): bool
    {
        return $this->update($user, $application);
    }

    public function withdraw(User $user, Application $application): bool
    {
        return $application->user_id === $user->id
            && $application->status->canBeWithdrawn()
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function viewReceipt(User $user, Application $application): bool
    {
        return $this->view($user, $application)
            && $application->application_number !== null;
    }
}
