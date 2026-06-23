<?php

namespace App\Policies;

use App\Models\ApplicationReview;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ApplicationReviewPolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_processes';

    public function view(User $user, ApplicationReview $applicationReview): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, ApplicationReview $applicationReview): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'update');
    }
}
