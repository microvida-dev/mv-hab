<?php

namespace App\Policies;

use App\Models\HousingVisit;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class HousingVisitPolicy
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('visits.view');
    }

    public function view(User $user, HousingVisit $visit): bool
    {
        if ($user->hasRole('candidate')) {
            return $visit->belongsToCandidate($user)
                && $user->hasPermission('visits.view')
                && $this->municipalScope
                    ->isStructurallyValidHousingVisit($visit);
        }

        return $user->hasPermission('visits.view')
            && $this->municipalScope->ownsHousingVisit($user, $visit);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('visits.create');
    }

    public function update(User $user, HousingVisit $visit): bool
    {
        if ($user->hasRole('candidate')) {
            return $visit->belongsToCandidate($user)
                && $visit->isActive()
                && $user->hasPermission('visits.update')
                && $this->municipalScope
                    ->isStructurallyValidHousingVisit($visit);
        }

        return $user->hasPermission('visits.update')
            && $this->municipalScope->ownsHousingVisit($user, $visit);
    }

    public function cancel(User $user, HousingVisit $visit): bool
    {
        return $this->update($user, $visit);
    }

    public function approve(User $user, HousingVisit $visit): bool
    {
        return $user->hasPermission('visits.approve')
            && $this->municipalScope->ownsHousingVisit($user, $visit);
    }

    public function reject(User $user, HousingVisit $visit): bool
    {
        return $user->hasPermission('visits.reject')
            && $this->municipalScope->ownsHousingVisit($user, $visit);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('visits.view')
            && $this->municipalScope
                ->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        HousingVisit $visit,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsHousingVisit($user, $visit);
    }

    public function confirmBackoffice(
        User $user,
        HousingVisit $visit,
    ): bool {
        return $user->hasPermission('visits.confirm')
            && $this->municipalScope->ownsHousingVisit($user, $visit);
    }

    public function completeBackoffice(
        User $user,
        HousingVisit $visit,
    ): bool {
        return $user->hasPermission('visits.complete')
            && $this->municipalScope->ownsHousingVisit($user, $visit);
    }

    public function markNoShowBackoffice(
        User $user,
        HousingVisit $visit,
    ): bool {
        return $user->hasPermission('visits.mark_no_show')
            && $this->municipalScope->ownsHousingVisit($user, $visit);
    }

    public function cancelBackoffice(
        User $user,
        HousingVisit $visit,
    ): bool {
        return $user->hasPermission('visits.cancel')
            && $this->municipalScope->ownsHousingVisit($user, $visit);
    }

    public function rejectBackoffice(
        User $user,
        HousingVisit $visit,
    ): bool {
        return $user->hasPermission('visits.reject')
            && $this->municipalScope->ownsHousingVisit($user, $visit);
    }
}
