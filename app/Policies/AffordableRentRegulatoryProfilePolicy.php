<?php

namespace App\Policies;

use App\Models\AffordableRentRegulatoryProfile;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Platform\PlatformOperatorScopeService;

final class AffordableRentRegulatoryProfilePolicy
{
    use ChecksPermissions;

    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->viewAnyBackoffice($user);
    }

    public function view(User $user, AffordableRentRegulatoryProfile $profile): bool
    {
        return $this->viewBackoffice($user, $profile);
    }

    public function create(User $user): bool
    {
        return $this->createBackoffice($user);
    }

    public function update(User $user, AffordableRentRegulatoryProfile $profile): bool
    {
        return $this->updateBackoffice($user, $profile);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'programs', 'view')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function viewBackoffice(User $user, AffordableRentRegulatoryProfile $profile): bool
    {
        return $this->viewAnyBackoffice($user);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'programs', 'create')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function updateBackoffice(User $user, AffordableRentRegulatoryProfile $profile): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'programs', 'update')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function activateBackoffice(User $user, AffordableRentRegulatoryProfile $profile): bool
    {
        return $this->updateBackoffice($user, $profile);
    }

    public function archiveBackoffice(User $user, AffordableRentRegulatoryProfile $profile): bool
    {
        return $this->updateBackoffice($user, $profile);
    }
}
