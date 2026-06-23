<?php

namespace App\Policies;

use App\Models\CandidateInteraction;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class CandidateInteractionPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'candidate_experience', 'view');
    }

    public function view(User $user, CandidateInteraction $interaction): bool
    {
        return $user->hasRole('candidate')
            ? $interaction->user_id === $user->id && $this->canAccess($user, 'candidate_experience', 'view')
            : $this->canAccess($user, 'candidate_experience', 'view');
    }
}
