<?php

namespace App\Policies;

use App\Models\HearingSubmission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HearingSubmissionPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'complaints', 'view');
    }

    public function view(User $user, HearingSubmission $submission): bool
    {
        return $user->hasRole('candidate')
            ? $submission->user_id === $user->id
            : $this->canAccess($user, 'complaints', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate') && $this->canAccess($user, 'complaints', 'create');
    }

    public function review(User $user, HearingSubmission $submission): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'update');
    }
}
