<?php

namespace App\Policies;

use App\Models\PermissionReview;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class PermissionReviewPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function view(User $user, PermissionReview $review): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function create(User $user): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function update(User $user, PermissionReview $review): bool
    {
        return $this->backoffice($user, 'audit');
    }
}
