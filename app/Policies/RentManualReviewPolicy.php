<?php

namespace App\Policies;

use App\Models\RentCalculation;
use App\Models\RentManualReview;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class RentManualReviewPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function createBackoffice(User $user, RentCalculation $calculation): bool
    {
        return $this->canMutateBackoffice($user, 'rent_manual_reviews.create')
            && $this->municipalScope->ownsRentCalculation($user, $calculation);
    }

    public function approveBackoffice(User $user, RentManualReview $review): bool
    {
        return $this->canMutateBackoffice($user, 'rent_manual_reviews.approve')
            && $this->municipalScope->ownsRentManualReview($user, $review);
    }

    public function rejectBackoffice(User $user, RentManualReview $review): bool
    {
        return $this->canMutateBackoffice($user, 'rent_manual_reviews.reject')
            && $this->municipalScope->ownsRentManualReview($user, $review);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', $action);
    }
}
