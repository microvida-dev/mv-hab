<?php

namespace App\Policies;

use App\Models\TenantPayment;
use App\Models\User;

class TenantPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['candidate', 'administrator', 'municipal_technician', 'financial_manager', 'auditor']);
    }

    public function view(User $user, TenantPayment $tenantPayment): bool
    {
        return $user->hasRole('candidate')
            ? (int) $tenantPayment->user_id === (int) $user->id
            : $user->hasRole(['administrator', 'municipal_technician', 'financial_manager', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'financial_manager']);
    }

    public function update(User $user, TenantPayment $tenantPayment): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'financial_manager']);
    }
}
