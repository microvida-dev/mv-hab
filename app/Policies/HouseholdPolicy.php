<?php

namespace App\Policies;

use App\Enums\AdhesionRegistrationStatus;
use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HouseholdPolicy
{
    use ChecksPermissions;

    private const MODULE = 'households';

    public function viewAny(User $user): bool
    {
        if ($user->hasRole('candidate')) {
            return $user->hasPermissionTo(self::MODULE, 'view');
        }

        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Household $household): bool
    {
        if ($user->hasRole('candidate')) {
            $registration = $household->adhesionRegistration;

            return $user->hasPermissionTo(self::MODULE, 'view')
                && $registration instanceof AdhesionRegistration
                && $registration->user_id === $user->id;
        }

        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('candidate')) {
            $registration = $user->adhesionRegistration()->first();

            return $user->hasPermissionTo(self::MODULE, 'create')
                && $registration instanceof AdhesionRegistration
                && ! $registration->household()->exists()
                && $this->registrationIsEditable($registration);
        }

        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Household $household): bool
    {
        if ($user->hasRole('candidate')) {
            $registration = $household->adhesionRegistration;

            return $user->hasPermissionTo(self::MODULE, 'update')
                && $registration instanceof AdhesionRegistration
                && $registration->user_id === $user->id
                && $this->registrationIsEditable($registration);
        }

        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Household $household): bool
    {
        if ($user->hasRole('candidate')) {
            return false;
        }

        return $this->canAccess($user, self::MODULE, 'delete');
    }

    private function registrationIsEditable(?AdhesionRegistration $registration): bool
    {
        $status = $registration?->getAttribute('status');

        if (is_string($status)) {
            $status = AdhesionRegistrationStatus::tryFrom($status);
        }

        return in_array($status, [AdhesionRegistrationStatus::Incomplete, AdhesionRegistrationStatus::Registered], true);
    }
}
