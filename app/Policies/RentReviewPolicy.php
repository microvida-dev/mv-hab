<?php

namespace App\Policies;

use App\Models\RentReview;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class RentReviewPolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'rent_reviews.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, RentReview $review): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsRentReview($user, $review);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutateBackoffice($user, 'rent_reviews.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function calculateBackoffice(User $user, RentReview $review): bool
    {
        return $this->canMutateBackoffice($user, 'rent_reviews.calculate')
            && $this->municipalScope->ownsRentReview($user, $review);
    }

    public function approveBackoffice(User $user, RentReview $review): bool
    {
        return $this->canMutateBackoffice($user, 'rent_reviews.approve')
            && $this->municipalScope->ownsRentReview($user, $review);
    }

    public function rejectBackoffice(User $user, RentReview $review): bool
    {
        return $this->canMutateBackoffice($user, 'rent_reviews.reject')
            && $this->municipalScope->ownsRentReview($user, $review);
    }

    public function applyBackoffice(User $user, RentReview $review): bool
    {
        return $this->canMutateBackoffice($user, 'rent_reviews.apply')
            && $this->municipalScope->ownsRentReview($user, $review);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', $action);
    }
}
