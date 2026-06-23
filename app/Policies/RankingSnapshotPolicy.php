<?php

namespace App\Policies;

use App\Models\RankingSnapshot;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RankingSnapshotPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, RankingSnapshot $snapshot): bool
    {
        return $this->viewAny($user);
    }

    public function lock(User $user, RankingSnapshot $snapshot): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'approve');
    }

    public function archive(User $user, RankingSnapshot $snapshot): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'update');
    }

    public function export(User $user, RankingSnapshot $snapshot): bool
    {
        return $this->canAccess($user, 'scoring', 'export');
    }
}
