<?php

namespace App\Policies;

use App\Models\BackupReview;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class BackupReviewPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function view(User $user, BackupReview $review): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function create(User $user): bool
    {
        return $this->backoffice($user, 'audit');
    }
}
