<?php

namespace App\Policies;

use App\Models\CurrentHousingSituation;
use App\Models\User;

class CurrentHousingSituationPolicy
{
    public function view(User $user, CurrentHousingSituation $situation): bool
    {
        return $this->owns($user, $situation)
            && $user->hasPermissionTo('households', 'view');
    }

    public function update(User $user, CurrentHousingSituation $situation): bool
    {
        $registration = $situation->adhesionRegistration;

        return $this->owns($user, $situation)
            && $user->hasPermissionTo('households', 'update')
            && $registration !== null
            && in_array($registration->status->value, ['incomplete', 'registered'], true);
    }

    private function owns(User $user, CurrentHousingSituation $situation): bool
    {
        return $user->hasRole('candidate')
            && $situation->adhesionRegistration?->user_id === $user->id;
    }
}
