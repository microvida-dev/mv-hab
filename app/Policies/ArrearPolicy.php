<?php

namespace App\Policies;

use App\Models\Arrear;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ArrearPolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, Arrear $arrear): bool
    {
        return $user->hasRole('candidate') ? $this->ownsFinanceRecord($user, $arrear) && $this->canViewFinance($user) : $this->canViewFinance($user);
    }

    public function update(User $user, Arrear $arrear): bool
    {
        return $this->canManageFinance($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'arrears.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, Arrear $arrear): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsArrear($user, $arrear);
    }

    public function resolveBackoffice(User $user, Arrear $arrear): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', 'arrears.resolve')
            && $this->municipalScope->ownsArrear($user, $arrear);
    }
}
