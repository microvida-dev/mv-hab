<?php

namespace App\Policies;

use App\Models\Municipality;
use App\Models\User;

class MunicipalityFeatureEntitlementPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPlatformScope($user)
            && $user->hasPermission('municipality_features.view');
    }

    public function view(User $user, Municipality $municipality): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Municipality $municipality): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->hasPlatformScope($user)
            && $user->hasPermission('municipality_features.update');
    }

    public function audit(User $user, Municipality $municipality): bool
    {
        return $this->hasPlatformScope($user)
            && $user->hasPermission('municipality_features.audit');
    }

    private function hasPlatformScope(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id === null;
    }
}
