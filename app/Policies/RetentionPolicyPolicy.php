<?php

namespace App\Policies;

use App\Models\RetentionPolicy;
use App\Models\User;
use App\Services\Rgpd\PrivacyMunicipalScopeService;

class RetentionPolicyPolicy
{
    public function __construct(
        private readonly PrivacyMunicipalScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('rgpd.retention.view');
    }

    public function view(User $user, RetentionPolicy $policy): bool
    {
        return $this->viewAny($user)
            && $this->scope->canUseRetentionPolicy($user, $policy);
    }

    public function create(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('rgpd.retention.manage');
    }

    public function update(User $user, RetentionPolicy $policy): bool
    {
        return $user->hasPermission('rgpd.retention.manage')
            && $this->scope->ownsMutableRetentionPolicy($user, $policy);
    }

    public function simulate(User $user, RetentionPolicy $policy): bool
    {
        return $user->hasPermission('rgpd.retention.manage')
            && $this->scope->canUseRetentionPolicy($user, $policy);
    }
}
