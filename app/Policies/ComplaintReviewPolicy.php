<?php

namespace App\Policies;

use App\Models\ComplaintReview;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ComplaintReviewPolicy
{
    use ChecksPermissions;

    public function view(User $user, ComplaintReview $review): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'complaints', 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'update');
    }
}
