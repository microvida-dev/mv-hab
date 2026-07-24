<?php

namespace App\Policies;

use App\Models\TenantChargeRun;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class TenantChargeRunPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'contracts', 'charge_runs.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, TenantChargeRun $run): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsTenantChargeRun($user, $run);
    }

    public function runBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'contracts', 'charge_runs.run')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }
}
