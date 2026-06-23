<?php

namespace App\Policies;

use App\Models\AdditionalInformationResponse;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AdditionalInformationResponsePolicy
{
    use ChecksPermissions;

    public function view(User $user, AdditionalInformationResponse $response): bool
    {
        return $user->hasRole('candidate')
            ? $response->user_id === $user->id
            : $this->canAccess($user, 'complaints', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate') && $this->canAccess($user, 'complaints', 'update');
    }

    public function review(User $user, AdditionalInformationResponse $response): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'update');
    }
}
