<?php

namespace App\Policies;

use App\Models\AuditEvent;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class AuditEventPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->audit($user);
    }

    public function view(User $user, AuditEvent $event): bool
    {
        return $this->audit($user);
    }

    public function export(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('audit_logs.export');
    }

    public function update(User $user, AuditEvent $event): bool
    {
        return false;
    }

    public function delete(User $user, AuditEvent $event): bool
    {
        return false;
    }
}
