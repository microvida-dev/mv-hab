<?php

namespace App\Policies;

use App\Models\AccessChangeEvent;
use App\Models\User;

class AccessAuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, AccessChangeEvent $event): bool
    {
        return $this->can($user, 'view');
    }

    private function can(User $user, string $action): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission("access_audit.{$action}");
    }
}
