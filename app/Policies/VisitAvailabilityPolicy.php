<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitAvailability;
use App\Services\Municipalities\MunicipalRecordScopeService;

class VisitAvailabilityPolicy
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('visits.view');
    }

    public function view(User $user, VisitAvailability $availability): bool
    {
        return $this->viewAny($user)
            && $this->municipalScope->ownsVisitAvailability(
                $user,
                $availability,
            );
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('visits.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function update(User $user, VisitAvailability $availability): bool
    {
        return $user->hasPermission('visits.update')
            && $this->municipalScope->ownsVisitAvailability(
                $user,
                $availability,
            );
    }

    public function delete(User $user, VisitAvailability $availability): bool
    {
        return $user->hasPermission('visits.delete')
            && $this->municipalScope->ownsVisitAvailability(
                $user,
                $availability,
            );
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'visits.availabilities.view',
        ) && $this->municipalScope
            ->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        VisitAvailability $availability,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsVisitAvailability(
                $user,
                $availability,
            );
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'visits.availabilities.create',
        ) && $this->municipalScope
            ->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(
        User $user,
        VisitAvailability $availability,
    ): bool {
        return $user->hasPermission(
            'visits.availabilities.update',
        ) && $this->municipalScope->ownsVisitAvailability(
            $user,
            $availability,
        );
    }

    public function deleteBackoffice(
        User $user,
        VisitAvailability $availability,
    ): bool {
        return $user->hasPermission(
            'visits.availabilities.delete',
        ) && $this->municipalScope->ownsVisitAvailability(
            $user,
            $availability,
        );
    }

    public function generateSlotsBackoffice(
        User $user,
        VisitAvailability $availability,
    ): bool {
        return $user->hasPermission(
            'visits.availabilities.generate_slots',
        ) && $this->municipalScope->ownsVisitAvailability(
            $user,
            $availability,
        );
    }
}
