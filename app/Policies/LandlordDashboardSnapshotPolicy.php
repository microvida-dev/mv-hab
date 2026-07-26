<?php

namespace App\Policies;

use App\Models\LandlordDashboardSnapshot;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class LandlordDashboardSnapshotPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager', 'auditor']);
    }

    public function view(User $user, LandlordDashboardSnapshot $landlordDashboardSnapshot): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['administrator', 'municipal_technician', 'financial_manager', 'maintenance_manager']);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'contracts', 'dashboard')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }
}
