<?php

namespace App\Policies;

use App\Models\HearingSubmission;
use App\Models\User;

class PreliminaryHearingSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('complaints', 'view');
    }

    public function view(User $user, HearingSubmission $submission): bool
    {
        return $submission->user_id === $user->id || $user->hasPermissionTo('complaints', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate');
    }

    public function update(User $user, HearingSubmission $submission): bool
    {
        return $user->hasPermissionTo('complaints', 'update');
    }
}
