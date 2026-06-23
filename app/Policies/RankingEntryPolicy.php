<?php

namespace App\Policies;

use App\Models\RankingEntry;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RankingEntryPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, RankingEntry $entry): bool
    {
        return $this->viewAny($user);
    }
}
