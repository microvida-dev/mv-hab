<?php

namespace App\Policies;

use App\Models\RankingUpdateRun;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RankingUpdateRunPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, RankingUpdateRun $run): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'scoring', 'update');
    }
}
