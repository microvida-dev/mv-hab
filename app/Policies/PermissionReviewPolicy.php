<?php

namespace App\Policies;

use App\Models\PermissionReview;
use App\Models\User;
use App\Services\Security\SecurityMunicipalScopeService;

class PermissionReviewPolicy
{
    public function __construct(
        private readonly SecurityMunicipalScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('permission_reviews.view');
    }

    public function view(User $user, PermissionReview $review): bool
    {
        return $this->viewAny($user)
            && $this->scope->ownsPermissionReview($user, $review);
    }

    public function create(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('permission_reviews.create');
    }

    public function update(User $user, PermissionReview $review): bool
    {
        return $user->hasPermission('permission_reviews.update')
            && $this->scope->ownsPermissionReview($user, $review);
    }

    public function complete(User $user, PermissionReview $review): bool
    {
        return $user->hasPermission('permission_reviews.complete')
            && $this->scope->ownsPermissionReview($user, $review);
    }
}
