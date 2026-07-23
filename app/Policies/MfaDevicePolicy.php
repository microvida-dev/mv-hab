<?php

namespace App\Policies;

use App\Models\MfaDevice;
use App\Models\User;

class MfaDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('security.manage_own_mfa');
    }

    public function view(User $user, MfaDevice $device): bool
    {
        return $this->viewAny($user)
            && $device->user_id === $user->id;
    }

    public function update(User $user, MfaDevice $device): bool
    {
        return $this->view($user, $device);
    }
}
