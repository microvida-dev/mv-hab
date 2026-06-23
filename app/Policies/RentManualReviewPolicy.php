<?php

namespace App\Policies;

use App\Models\RentManualReview;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RentManualReviewPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contracts', 'view');
    }

    public function view(User $user, RentManualReview $rentManualReview): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function approve(User $user, RentManualReview $rentManualReview): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }
}
