<?php

namespace App\Policies;

use App\Models\LotteryParticipant;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class LotteryParticipantPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, LotteryParticipant $participant): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, LotteryParticipant $participant): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }
}
