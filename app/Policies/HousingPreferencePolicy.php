<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\HousingPreference;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HousingPreferencePolicy
{
    use ChecksPermissions;

    public function __construct(
        private readonly ApplicationPolicy $applications,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, HousingPreference $preference): bool
    {
        $application = $preference->application;

        return $application instanceof Application
            && $this->applications->view($user, $application)
            && $this->canAccess($user, 'allocations', 'view');
    }

    public function update(User $user, Application $application): bool
    {
        return $this->applications->update($user, $application)
            && $application->status === ApplicationStatus::Draft
            && $application->locked_at === null
            && $application->housingPreferences()->whereNotNull('locked_at')->doesntExist()
            && $application->allocations()->doesntExist()
            && $this->canAccess($user, 'allocations', 'update');
    }
}
