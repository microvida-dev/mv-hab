<?php

namespace App\Policies;

use App\Enums\AdhesionRegistrationStatus;
use App\Models\AdhesionRegistration;
use App\Models\HouseholdMember;
use App\Models\User;

class HouseholdMemberPolicy
{
    public function view(User $user, HouseholdMember $member): bool
    {
        return $this->owns($user, $member)
            && $user->hasPermissionTo('households', 'view');
    }

    public function update(User $user, HouseholdMember $member): bool
    {
        return $this->owns($user, $member)
            && $user->hasPermissionTo('households', 'update')
            && $this->registrationIsEditable($this->registration($member));
    }

    public function delete(User $user, HouseholdMember $member): bool
    {
        return $this->owns($user, $member)
            && $user->hasPermissionTo('households', 'delete')
            && $this->registrationIsEditable($this->registration($member));
    }

    private function owns(User $user, HouseholdMember $member): bool
    {
        $registration = $member->adhesionRegistration;

        return $user->hasRole('candidate')
            && $registration instanceof AdhesionRegistration
            && $registration->user_id === $user->id;
    }

    private function registration(HouseholdMember $member): ?AdhesionRegistration
    {
        $registration = $member->adhesionRegistration;

        return $registration instanceof AdhesionRegistration ? $registration : null;
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
