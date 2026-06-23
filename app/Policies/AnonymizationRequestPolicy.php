<?php

namespace App\Policies;

use App\Models\AnonymizationRequest;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class AnonymizationRequestPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->privacy($user);
    }

    public function view(User $user, AnonymizationRequest $request): bool
    {
        return $this->privacy($user);
    }

    public function create(User $user): bool
    {
        return $this->privacy($user, 'create');
    }

    public function run(User $user, AnonymizationRequest $request): bool
    {
        return $this->privacy($user, 'approve');
    }
}
