<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitSlot;
use App\Services\Municipalities\MunicipalRecordScopeService;

class VisitSlotPolicy
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('visits.view');
    }

    public function view(User $user, VisitSlot $slot): bool
    {
        return $this->viewAny($user)
            && $this->municipalScope->ownsVisitSlot($user, $slot);
    }

    public function update(User $user, VisitSlot $slot): bool
    {
        return $user->hasPermission('visits.update')
            && $this->municipalScope->ownsVisitSlot($user, $slot);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('visits.slots.view')
            && $this->municipalScope
                ->hasMunicipalOrGlobalScope($user);
    }

    public function blockBackoffice(
        User $user,
        VisitSlot $slot,
    ): bool {
        return $user->hasPermission('visits.slots.block')
            && $this->municipalScope->ownsVisitSlot($user, $slot);
    }

    public function unblockBackoffice(
        User $user,
        VisitSlot $slot,
    ): bool {
        return $user->hasPermission('visits.slots.unblock')
            && $this->municipalScope->ownsVisitSlot($user, $slot);
    }
}
