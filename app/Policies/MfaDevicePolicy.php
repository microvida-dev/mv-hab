<?php

namespace App\Policies;

use App\Models\MfaDevice;
use App\Models\User;

class MfaDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate');
    }

    public function view(User $user, MfaDevice $device): bool
    {
        return $device->user_id === $user->id || $user->hasRole('administrator');
    }

    public function update(User $user, MfaDevice $device): bool
    {
        return $device->user_id === $user->id || $user->hasRole('administrator');
    }
}
