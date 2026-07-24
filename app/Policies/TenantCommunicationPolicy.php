<?php

namespace App\Policies;

use App\Models\TenantCommunication;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class TenantCommunicationPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['candidate', 'administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager', 'auditor']);
    }

    public function view(User $user, TenantCommunication $tenantCommunication): bool
    {
        return $user->hasRole('candidate')
            ? (int) $tenantCommunication->user_id === (int) $user->id
            : $user->hasRole(['administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['candidate', 'administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager']);
    }

    public function update(User $user, TenantCommunication $tenantCommunication): bool
    {
        return $this->view($user, $tenantCommunication)
            && ! $user->hasRole('auditor');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'contracts', 'communications.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, TenantCommunication $communication): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsTenantCommunication($user, $communication);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'contracts', 'communications.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function messageBackoffice(User $user, TenantCommunication $communication): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'contracts', 'communications.message')
            && $this->municipalScope->ownsTenantCommunication($user, $communication);
    }
}
