<?php

namespace App\Policies;

use App\Models\AdhesionRegistration;
use App\Models\User;

class AdhesionRegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('administrator');
    }

    public function view(User $user, AdhesionRegistration $registration): bool
    {
        return $user->hasRole('administrator')
            || ($user->hasRole('candidate') && $registration->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate')
            && $user->hasPermissionTo('adhesion_registrations', 'create')
            && ! $user->adhesionRegistration()->exists();
    }

    public function update(User $user, AdhesionRegistration $registration): bool
    {
        return $user->hasRole('candidate')
            && $user->hasPermissionTo('adhesion_registrations', 'update')
            && $registration->user_id === $user->id
            && in_array($registration->status->value, ['incomplete', 'registered'], true);
    }

    public function finalize(User $user, AdhesionRegistration $registration): bool
    {
        return $this->update($user, $registration)
            && $registration->status->value === 'incomplete';
    }

    public function cancel(User $user, AdhesionRegistration $registration): bool
    {
        return $user->hasRole('candidate')
            && $registration->user_id === $user->id
            && $registration->canBeCancelled();
    }

    public function delete(User $user, AdhesionRegistration $registration): bool
    {
        return $user->hasRole('candidate')
            && $registration->user_id === $user->id
            && $registration->canBeRemoved();
    }
}
