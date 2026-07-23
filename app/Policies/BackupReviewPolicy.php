<?php

namespace App\Policies;

use App\Models\BackupReview;
use App\Models\User;
use App\Services\Security\SecurityMunicipalScopeService;

class BackupReviewPolicy
{
    public function __construct(
        private readonly SecurityMunicipalScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('security.view');
    }

    public function view(User $user, BackupReview $review): bool
    {
        return $this->viewAny($user)
            && $this->scope->backupReviews(
                BackupReview::query()->whereKey($review),
                $user,
            )->exists();
    }

    public function create(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('security.update');
    }
}
