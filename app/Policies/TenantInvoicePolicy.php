<?php

namespace App\Policies;

use App\Models\TenantInvoice;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class TenantInvoicePolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'payments', 'invoices.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, TenantInvoice $invoice): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsTenantInvoice($user, $invoice);
    }

    public function generateBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'payments', 'invoices.generate')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }
}
