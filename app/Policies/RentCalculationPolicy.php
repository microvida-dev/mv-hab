<?php

namespace App\Policies;

use App\Models\RentCalculation;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class RentCalculationPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contracts', 'view');
    }

    public function view(User $user, RentCalculation $rentCalculation): bool
    {
        return $user->hasRole('candidate')
            ? $rentCalculation->user_id === $user->id && $this->canAccess($user, 'contracts', 'view')
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'create');
    }

    public function update(User $user, RentCalculation $rentCalculation): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function approve(User $user, RentCalculation $rentCalculation): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'rent_calculations.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, RentCalculation $calculation): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsRentCalculation($user, $calculation);
    }

    public function calculateBackoffice(User $user): bool
    {
        return $this->canMutateBackoffice($user, 'rent_calculations.calculate')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function recalculateBackoffice(User $user, RentCalculation $calculation): bool
    {
        return $this->canMutateBackoffice($user, 'rent_calculations.recalculate')
            && $this->municipalScope->ownsRentCalculation($user, $calculation);
    }

    public function approveBackoffice(User $user, RentCalculation $calculation): bool
    {
        return $this->canMutateBackoffice($user, 'rent_calculations.approve')
            && $this->municipalScope->ownsRentCalculation($user, $calculation);
    }

    public function rejectBackoffice(User $user, RentCalculation $calculation): bool
    {
        return $this->canMutateBackoffice($user, 'rent_calculations.reject')
            && $this->municipalScope->ownsRentCalculation($user, $calculation);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', $action);
    }
}
