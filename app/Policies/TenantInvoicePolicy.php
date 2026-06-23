<?php

namespace App\Policies;

use App\Models\TenantInvoice;
use App\Models\User;

class TenantInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['candidate', 'administrator', 'municipal_technician', 'financial_manager', 'auditor']);
    }

    public function view(User $user, TenantInvoice $tenantInvoice): bool
    {
        return $user->hasRole('candidate')
            ? (int) $tenantInvoice->user_id === (int) $user->id
            : $user->hasRole(['administrator', 'municipal_technician', 'financial_manager', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'financial_manager']);
    }

    public function update(User $user, TenantInvoice $tenantInvoice): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'financial_manager']);
    }
}
