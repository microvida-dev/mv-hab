<?php

namespace App\Policies;

use App\Models\TenantContractAccess;
use App\Models\User;

class TenantContractAccessPolicy
{
    public function view(User $user, TenantContractAccess $tenantContractAccess): bool
    {
        return (int) $tenantContractAccess->user_id === (int) $user->id
            || $user->hasRole(['administrator', 'municipal_technician', 'auditor']);
    }

    public function update(User $user, TenantContractAccess $tenantContractAccess): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician']);
    }
}
