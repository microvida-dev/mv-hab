<?php

namespace App\Policies;

use App\Models\HousingUnit;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HousingUnitPolicy
{
    use ChecksPermissions;

    private const MODULE = 'housing_units';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, HousingUnit $housingUnit): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, HousingUnit $housingUnit): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, HousingUnit $housingUnit): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }

    public function updatePublicProfile(User $user, HousingUnit $housingUnit): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function publishPublicProfile(User $user, HousingUnit $housingUnit): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function previewPublicProfile(User $user, HousingUnit $housingUnit): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }

    public function viewBackoffice(User $user, HousingUnit $housingUnit): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'create');
    }

    public function updateBackoffice(User $user, HousingUnit $housingUnit): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function deleteBackoffice(User $user, HousingUnit $housingUnit): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'delete');
    }

    public function updatePublicProfileBackoffice(User $user, HousingUnit $housingUnit): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function publishPublicProfileBackoffice(User $user, HousingUnit $housingUnit): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function previewPublicProfileBackoffice(User $user, HousingUnit $housingUnit): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }
}
