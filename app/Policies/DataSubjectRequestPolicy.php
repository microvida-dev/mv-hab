<?php

namespace App\Policies;

use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class DataSubjectRequestPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('candidate') || $this->privacy($user);
    }

    public function view(User $user, DataSubjectRequest $request): bool
    {
        return $request->user_id === $user->id || $this->privacy($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DataSubjectRequest $request): bool
    {
        return $this->privacy($user, 'update');
    }
}
