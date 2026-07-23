<?php

namespace App\Policies;

use App\Models\Municipality;
use App\Models\User;
use App\Services\Platform\PlatformOperatorScopeService;

class MunicipalityFeatureEntitlementPolicy
{
    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->platformScope->hasGlobalScope($user)
            && $user->hasPermission('municipality_features.view');
    }

    public function view(User $user, Municipality $municipality): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Municipality $municipality): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->platformScope->hasGlobalScope($user)
            && $user->hasPermission('municipality_features.update');
    }

    public function audit(User $user, Municipality $municipality): bool
    {
        return $this->platformScope->hasGlobalScope($user)
            && $user->hasPermission('municipality_features.audit');
    }
}
