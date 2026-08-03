<?php

namespace App\Policies;

use App\Models\MfaDevice;
use App\Models\User;
use App\Services\Platform\PlatformOperatorScopeService;

class MfaDevicePolicy
{
    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return ($user->status ?? 'active') === 'active'
            && $user->hasPermission('security.manage_own_mfa')
            && (
                $user->municipality_id !== null
                || $this->platformScope->hasGlobalScope($user)
            );
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
