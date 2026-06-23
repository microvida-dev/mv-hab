<?php

namespace App\Policies;

use App\Models\RentReview;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class RentReviewPolicy
{
    use ChecksFinanceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, RentReview $rentReview): bool
    {
        return $user->hasRole('candidate') ? $this->ownsFinanceRecord($user, $rentReview) && $this->canViewFinance($user) : $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateFinance($user);
    }

    public function update(User $user, RentReview $rentReview): bool
    {
        return $this->canManageFinance($user);
    }
}
