<?php

namespace App\Policies;

use App\Models\TenantPayment;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class TenantPaymentPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'payments', 'tenant.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, TenantPayment $payment): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsTenantPayment($user, $payment);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'payments', 'tenant.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function confirmBackoffice(User $user, TenantPayment $payment): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'payments', 'tenant.confirm')
            && $this->municipalScope->ownsTenantPayment($user, $payment);
    }
}
