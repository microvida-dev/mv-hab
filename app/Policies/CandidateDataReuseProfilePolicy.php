<?php

namespace App\Policies;

use App\Models\CandidateDataReuseProfile;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class CandidateDataReuseProfilePolicy
{
    use ChecksPermissions;

    public function view(User $user, CandidateDataReuseProfile $candidateDataReuseProfile): bool
    {
        return $candidateDataReuseProfile->user_id === $user->id
            && $this->canAccess($user, 'simulator', 'view');
    }

    public function update(User $user, CandidateDataReuseProfile $candidateDataReuseProfile): bool
    {
        return $candidateDataReuseProfile->user_id === $user->id
            && $this->canAccess($user, 'simulator', 'update');
    }
}
