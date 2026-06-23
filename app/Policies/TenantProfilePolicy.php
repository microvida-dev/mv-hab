<?php

namespace App\Policies;

use App\Models\TenantProfile;
use App\Models\User;

class TenantProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'auditor']);
    }

    public function view(User $user, TenantProfile $tenantProfile): bool
    {
        return (int) $tenantProfile->user_id === (int) $user->id
            || $user->hasRole(['administrator', 'municipal_technician', 'auditor']);
    }

    public function update(User $user, TenantProfile $tenantProfile): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician']);
    }
}
