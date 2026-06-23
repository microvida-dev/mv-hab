<?php

namespace App\Policies;

use App\Models\TenantChargeRun;
use App\Models\User;

class TenantChargeRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'financial_manager', 'auditor']);
    }

    public function view(User $user, TenantChargeRun $tenantChargeRun): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'financial_manager']);
    }
}
