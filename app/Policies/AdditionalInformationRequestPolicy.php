<?php

namespace App\Policies;

use App\Models\AdditionalInformationRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AdditionalInformationRequestPolicy
{
    use ChecksPermissions;

    public function view(User $user, AdditionalInformationRequest $request): bool
    {
        return $user->hasRole('candidate')
            ? $request->user_id === $user->id && $this->canAccess($user, 'complaints', 'view')
            : $this->canAccess($user, 'complaints', 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'update');
    }

    public function update(User $user, AdditionalInformationRequest $request): bool
    {
        return $this->create($user);
    }
}
